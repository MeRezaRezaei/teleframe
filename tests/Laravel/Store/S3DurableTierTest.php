<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Store;

use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\Restorer;
use MeRezaRezaei\Teleframe\Laravel\Store\AwsSdkS3Gateway;
use MeRezaRezaei\Teleframe\Laravel\Store\S3DurableVault;
use MeRezaRezaei\Teleframe\Laravel\Store\S3GatewayContract;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * THE S3 GATEWAY AS A DURABLE BACKUP TIER (Cycle 38 — Goal D).
 *
 * The defect this file closes, in two halves, because they are two different
 * sizes of lie:
 *
 *  1. CONFIGURABILITY. `S3GatewayContract` was bound with the endpoint, region
 *     and bucket as LITERALS ('http://127.0.0.1:9010', 'teleframe-tstore'),
 *     while only the credentials were env-driven. A host that reads the
 *     operator's "THE S3 IS GOOD" and points at real S3 got a client that
 *     dialled LOCALHOST MinIO, and the failure surfaced only at first use.
 *     A custom S3 endpoint was unreachable by configuration.
 *
 *  2. REACHABILITY AS A DURABLE TIER. The gateway's ONLY consumer in the whole
 *     package was `MinioHotCache` — the tstore HOT CACHE, which is by
 *     definition evictable and not a backup. So "treat telegram as a backup
 *     storage … the s3 is good" never reached the durable path: `teleframe:
 *     backup` could write to Telegram or to a process-local InMemoryVault and
 *     to nothing else. This half wires the gateway in as a real
 *     `VaultInterface`, so the operator's S3 is a BACKUP TIER.
 *
 * WHY `S3DurableVault` IMPLEMENTS THE ENGINE'S `VaultInterface`:
 * `VaultInterface`'s own docblock says "Message ids are opaque strings to keep
 * the interface transport-agnostic", and it already has two transports
 * (TelegramVault, InMemoryVault). S3 is a third, and this file uses the SAME
 * BackupRunner the Telegram path uses — so a run written to S3 is a real
 * backup run, encrypted and deduped identically, not a bespoke side channel.
 * The layering matches MinioHotCache exactly: the engine never learns about
 * S3; the Laravel Store layer adapts it.
 *
 * NO CLOUD, NO CREDENTIALS: the whole file runs on `FakeS3Gateway`, the
 * in-memory S3GatewayContract double this repo already ships
 * (tests/Laravel/Store/FakeS3Gateway.php). No MinIO process, no AWS account,
 * no network. That is the point of testing at the VaultInterface seam.
 */
final class S3DurableTierTest extends TestCase
{
    /** A NON-DEFAULT endpoint — the assertion that cannot pass against a literal. */
    private const ENDPOINT = 'https://s3.eu-central-1.amazonaws.com';

    private const REGION = 'eu-central-1';

    private const BUCKET = 'teleframe-backup-acct-9';

    private const ENDPOINT_ENV = 'TELEFRAME_TSTORE_S3_ENDPOINT';

    private const REGION_ENV = 'TELEFRAME_TSTORE_S3_REGION';

    private const BUCKET_ENV = 'TELEFRAME_TSTORE_S3_BUCKET';

    protected function tearDown(): void
    {
        foreach ([self::ENDPOINT_ENV, self::REGION_ENV, self::BUCKET_ENV] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        parent::tearDown();
    }

    /**
     * One `[$name, $value]` pair for the destructuring `foreach` below.
     *
     * @return array{0: string, 1: string}
     */
    private static function env(string $name, string $value): array
    {
        return [$name, $value];
    }

    /**
     * Re-read the SHIPPED config file into the booted container, so a
     * putenv() made in a test body is observable.
     *
     * This is a HARNESS step, not a production one. Laravel evaluates
     * config files ONCE at bootstrap, after it has loaded .env — so in
     * production the env chain is read in the right order and nothing here
     * is needed. In a test the app is ALREADY booted when the body runs, so
     * the repository holds a snapshot taken before the putenv(); asserting
     * against that snapshot would test the snapshot, not the configuration.
     *
     * Only the `teleframe.tstore.s3` slice is replaced, and it is re-read
     * FROM DISK rather than hand-written into the repository, so the
     * env -> config -> gateway chain stays the thing actually under test:
     * writing the endpoint straight into config would pass even if the
     * shipped file still hardcoded it.
     */
    private function reloadShippedS3Config(): void
    {
        $fresh = require dirname(__DIR__, 3).'/src/Laravel/config/teleframe.php';

        $this->app->make('config')->set('teleframe.tstore.s3', $fresh['tstore']['s3']);
    }

    // ── HALF 1: the endpoint, region and bucket are CONFIGURABLE ──────────

    /**
     * The gate for half 1, at the level an operator meets it. Before the fix
     * this is RED: the provider's closure passed the endpoint as a literal, so
     * the resolved gateway carried 'http://127.0.0.1:9010' no matter what the
     * env said. There is no way to assert a literal against a configured value.
     */
    public function test_the_configured_endpoint_reaches_the_resolved_gateway(): void
    {
        putenv(self::ENDPOINT_ENV.'='.self::ENDPOINT);
        $this->reloadShippedS3Config();

        $gateway = $this->app->make(S3GatewayContract::class);

        self::assertInstanceOf(AwsSdkS3Gateway::class, $gateway);
        self::assertSame(
            self::ENDPOINT,
            $gateway->config()['endpoint'],
            'the S3 endpoint is a literal, not configuration: a host pointing at real S3 dials localhost MinIO',
        );
    }

    /** Region and bucket travel the same chain — one literal, three keys. */
    public function test_the_configured_region_and_bucket_reach_the_resolved_gateway(): void
    {
        putenv(self::REGION_ENV.'='.self::REGION);
        putenv(self::BUCKET_ENV.'='.self::BUCKET);
        $this->reloadShippedS3Config();

        $config = $this->app->make(S3GatewayContract::class)->config();

        self::assertSame(self::REGION, $config['region']);
        self::assertSame(self::BUCKET, $config['bucket']);
    }

    /**
     * Link 1, isolated: the shipped config file reads these exact env names
     * for these exact keys. If the key were renamed or the env name drifted,
     * this fails before the provider is even involved.
     */
    public function test_the_env_vars_reach_the_merged_config(): void
    {
        foreach ([
            self::env(self::ENDPOINT_ENV, self::ENDPOINT),
            self::env(self::REGION_ENV, self::REGION),
            self::env(self::BUCKET_ENV, self::BUCKET),
        ] as [$name, $value]) {
            putenv($name.'='.$value);
        }

        $reloaded = require dirname(__DIR__, 3).'/src/Laravel/config/teleframe.php';

        self::assertSame(self::ENDPOINT, $reloaded['tstore']['s3']['endpoint']);
        self::assertSame(self::REGION, $reloaded['tstore']['s3']['region']);
        self::assertSame(self::BUCKET, $reloaded['tstore']['s3']['bucket']);
    }

    /**
     * THE BACK-COMPAT ARM, and it is not decoration: "no existing host changes
     * behaviour" is a promise about DEFAULTS. A host that configured nothing
     * must still get exactly the gateway it got before, literals included.
     */
    public function test_an_unconfigured_host_keeps_the_shipped_defaults(): void
    {
        $config = $this->app->make(S3GatewayContract::class)->config();

        self::assertSame('http://127.0.0.1:9010', $config['endpoint']);
        self::assertSame('us-east-1', $config['region']);
        self::assertSame('teleframe-tstore', $config['bucket']);
    }

    // ── HALF 2: the gateway IS a durable backup tier ───────────────────────

    /**
     * THE CENTREPIECE OF HALF 2, and it cannot pass while the gateway's only
     * consumer is the hot cache.
     *
     * A real `teleframe:backup` run — the shipped `BackupRunner`, the shipped
     * `Restorer`, the shipped chunk encryption — against an S3-backed vault,
     * with the bytes living ONLY in the fake gateway and nowhere else on the
     * machine. The file is unlinked before the restore, so "restored" cannot
     * mean "read off the disk it came from".
     */
    public function test_a_backup_written_to_the_gateway_is_restored_out_of_it(): void
    {
        $gateway = new FakeS3Gateway;
        $vault = new S3DurableVault('set-a', $gateway);

        $source = (string) tempnam(sys_get_temp_dir(), 'tf-s3-backup-');
        $restoreTo = sys_get_temp_dir().'/tf-s3-restore-'.bin2hex(random_bytes(6));
        file_put_contents($source, 'the operator wants a real backup of this file');

        try {
            $report = BackupRunner::run(
                ['paths' => [$source], 'set' => 'set-a', 'chunk_size' => 64],
                $vault,
                'a passphrase with real entropy',
            );

            self::assertSame(1, $report['files'], 'the runner walked one file');
            self::assertGreaterThan(0, $report['uploaded'], 'and uploaded at least one chunk to the gateway');
            self::assertNotSame([], $gateway->listAsMap(), 'the gateway really holds the backup');

            // The bytes now exist ONLY in the gateway.
            unlink($source);
            self::assertFalse(is_file($source), 'precondition: the local copy is gone before the restore');

            $restored = Restorer::restore($vault, 'a passphrase with real entropy', $restoreTo);

            self::assertSame(1, $restored['files']);
            self::assertSame(
                'the operator wants a real backup of this file',
                (string) file_get_contents($restoreTo.'/'.basename($source)),
                'the restored bytes came out of the S3 gateway, byte for byte',
            );
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
            @unlink($restoreTo.'/'.basename($source));
            @rmdir($restoreTo);
        }
    }

    /**
     * The manifest is the INDEX of the whole set, and VaultInterface requires
     * the highest GENERATION to win rather than the newest write — a rollback
     * must not become truth. This pins that on the S3 tier specifically, since
     * it is the tier whose key ordering is list-ordered rather than
     * message-ordered, and that difference is exactly where a "latest wins"
     * implementation would quietly be wrong.
     */
    public function test_the_s3_tier_reads_the_highest_generation_not_the_newest_write(): void
    {
        $gateway = new FakeS3Gateway;
        $vault = new S3DurableVault('set-a', $gateway);

        // Written NEWEST-LAST but with the LOWEST generation: a rollback.
        $vault->putManifest((string) json_encode(['generation' => 5, 'files' => []]));
        $vault->putManifest((string) json_encode(['generation' => 2, 'files' => []]));

        self::assertSame(5, $vault->getLatestManifest()['generation']);
    }

    /**
     * The chunks a manifest references are stored under their CIPHERTEXT hash,
     * never under the plaintext content address — the same rule TelegramVault
     * and InMemoryVault follow, because a document named by its plaintext hash
     * confirms a chosen plaintext to anyone who can list the bucket. This pins
     * that the S3 tier keeps the address book rather than dropping it.
     */
    public function test_the_s3_tier_resolves_a_plaintext_address_through_the_ciphertext_book(): void
    {
        $gateway = new FakeS3Gateway;
        $vault = new S3DurableVault('set-a', $gateway);

        $plainHash = hash('sha256', 'plaintext chunk bytes');
        $cipherHash = hash('sha256', 'ciphertext chunk bytes');
        $cipherBytes = 'ciphertext chunk bytes';

        $vault->putChunk($cipherHash, $cipherBytes);
        $vault->putManifest((string) json_encode([
            'generation' => 1,
            'files' => [],
            'chunk_hashes' => [$plainHash],
            'wire_chunks' => [$cipherHash => $plainHash],
        ]));

        self::assertSame($cipherBytes, $vault->getChunk($plainHash));
        self::assertArrayNotHasKey($plainHash, $gateway->listAsMap(), 'the plaintext address is not a key on the wire');
    }

    /**
     * THE WIRING, not just the class. Before this the provider's vault factory
     * accepted only 'memory' and 'telegram' and threw on anything else, so the
     * gateway was unreachable from `teleframe:backup` no matter how the class
     * was written. The driver is selected by configuration, exactly like the
     * other two, and it must resolve out of the CONTAINER.
     */
    public function test_the_container_builds_an_s3_vault_for_the_s3_backup_driver(): void
    {
        $gateway = new FakeS3Gateway;
        $this->app->instance(S3GatewayContract::class, $gateway);

        config()->set('teleframe.backup.driver', 's3');
        config()->set('teleframe.backup.passphrase', 'a passphrase with real entropy');

        /** @var callable(string): \MeRezaRezaei\Teleframe\Backup\VaultInterface $factory */
        $factory = $this->app->make(\MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand::VAULT_FACTORY_KEY);
        $vault = $factory('set-a');

        self::assertInstanceOf(S3DurableVault::class, $vault, 'driver=s3 must resolve an S3 durable tier');
        self::assertSame($gateway, $vault->gateway(), 'and it must be the CONTAINER\'s gateway, not a private one');
    }

    // ── CYCLE 39 GOAL F: the manifest index is SEALED, and the seal is what
    //    makes `generation` unforgeable rather than merely private ──────────

    /**
     * THE BACKWARD-COMPATIBILITY HALF, stated as a test because it is a
     * contract: the manifest passphrase is OPTIONAL, and a vault built
     * without one behaves EXACTLY as before — the object in the bucket IS the
     * JSON `BackupRunner` handed over, and it reads back. This is what keeps
     * the tier plain-PHP-constructible with no secret in hand (the committed
     * standalone smoke harness constructs it that way), and it is why the
     * four existing tests above still describe a supported configuration.
     *
     * Before Goal F's provider wiring this test could not distinguish "not
     * sealed" from "sealed and broken", because the constructor took no
     * passphrase at all; it now pins the unsealed mode on purpose.
     */
    public function test_a_vault_without_a_passphrase_keeps_the_plaintext_manifest_behaviour(): void
    {
        $gateway = new FakeS3Gateway;
        $vault = new S3DurableVault('set-a', $gateway);

        $json = (string) json_encode(['generation' => 4, 'files' => [], 'created' => '2026-09-30T00:00:00+00:00']);
        $vault->putManifest($json);

        self::assertSame(
            [$json],
            array_values($gateway->listAsMap()),
            'with no passphrase the stored object IS the plaintext JSON, unchanged',
        );
        self::assertSame(4, $vault->getLatestManifest()['generation'], 'and it round-trips');
    }

    /**
     * THE FIX, part (a): with a passphrase the bytes in the bucket are NOT
     * plaintext JSON. Asserted on the RAW stored object, not on a decrypt
     * round trip, because a round trip passes just as happily when nothing
     * was ever sealed.
     */
    public function test_a_sealed_manifest_is_not_stored_as_plaintext_json(): void
    {
        $gateway = new FakeS3Gateway;
        $vault = new S3DurableVault('set-a', $gateway, 'a passphrase with real entropy');

        $json = (string) json_encode([
            'generation' => 1,
            'files' => [['path' => '/etc/teleframe/secret.env', 'size' => 12]],
            'created' => '2026-09-30T00:00:00+00:00',
        ]);
        $vault->putManifest($json);

        $stored = array_values($gateway->listAsMap())[0];

        self::assertNotSame($json, $stored, 'the stored bytes are not the JSON that was handed over');
        self::assertStringNotContainsString('secret.env', $stored, 'the file tree never reaches the bucket');
        self::assertStringNotContainsString('"generation"', $stored, 'nor the generation counter that orders the set');
        self::assertFalse(
            is_array(json_decode($stored, true)),
            'the stored object does not even decode as JSON — it is sealed, not plaintext: '
                .json_last_error_msg().' in '.var_export(substr($stored, 0, 32), true),
        );
        self::assertNotSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'and the decode itself FAILED — a bare `null` literal or empty string would decode without error, which is why the is_array() check above is the load-bearing one',
        );
        self::assertSame(
            strlen($json) + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES,
            strlen($stored),
            'the sealed object is EXACTLY the secretstream envelope of the plaintext — '
                .SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES.'B salt header + plaintext + '
                .SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES.'B MAC — so nothing else is stored',
        );
    }

    /**
     * THE FIX, part (b): sealing is not a write-only change. The very same
     * bytes must come back out of a FRESH process (a cron prune, an incident
     * restore), and a full BackupRunner → Restorer round trip through the
     * sealed index must still restore the file byte for byte.
     */
    public function test_a_sealed_manifest_still_restores_a_real_backup(): void
    {
        $gateway = new FakeS3Gateway;
        $passphrase = 'a passphrase with real entropy';
        $source = (string) tempnam(sys_get_temp_dir(), 'tf-s3-sealed-');
        $restoreTo = sys_get_temp_dir().'/tf-s3-sealed-restore-'.bin2hex(random_bytes(6));
        file_put_contents($source, 'sealed index, unsealed outcome');

        try {
            $report = BackupRunner::run(
                ['paths' => [$source], 'set' => 'set-a', 'chunk_size' => 64],
                new S3DurableVault('set-a', $gateway, $passphrase),
                $passphrase,
            );
            self::assertGreaterThan(0, $report['uploaded']);

            // A FRESH vault, as a later process would build it.
            unlink($source);
            $restored = Restorer::restore(
                new S3DurableVault('set-a', $gateway, $passphrase),
                $passphrase,
                $restoreTo,
            );

            self::assertSame(1, $restored['files']);
            self::assertSame(
                'sealed index, unsealed outcome',
                (string) file_get_contents($restoreTo.'/'.basename($source)),
                'the sealed index led the restore to the right chunks',
            );
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
            @unlink($restoreTo.'/'.basename($source));
            @rmdir($restoreTo);
        }
    }

    /**
     * THE FIX, part (c) — THE ONE THAT MATTERS, because the unsealed index was
     * not only a disclosure but a WRITE PRIMITIVE. S3DurableVault ranks
     * "latest" by the `generation` inside the body, so with a plaintext body
     * anyone who can put an object in the bucket chooses the ranking key: a
     * manifest injected with generation => PHP_INT_MAX is read back as
     * 9223372036854775807 by a fresh process and pins the set to that content
     * for the life of the set. Under a passphrase the injected object is
     * unreadable and simply cannot compete.
     */
    public function test_a_tampered_generation_cannot_reorder_a_sealed_set(): void
    {
        $gateway = new FakeS3Gateway;
        $passphrase = 'a passphrase with real entropy';

        $writer = new S3DurableVault('set-a', $gateway, $passphrase);
        $writer->putManifest((string) json_encode(['generation' => 5, 'files' => []]));

        // The attacker: a raw object write into the free slot between two
        // real manifests, no vault, no key — exactly what "can list the
        // bucket" is enough to do.
        $gateway->put(
            'teleframe-backup/set-a/manifests/'.str_pad('2', 16, '0', STR_PAD_LEFT),
            (string) json_encode(['generation' => PHP_INT_MAX, 'files' => []]),
        );
        $writer->putManifest((string) json_encode(['generation' => 6, 'files' => []]));

        // A FRESH process reading the set.
        $reader = new S3DurableVault('set-a', $gateway, $passphrase);

        self::assertSame(
            6,
            $reader->getLatestManifest()['generation'],
            'the injected generation must not become the ordering key',
        );
        self::assertSame(
            [6, 5],
            array_map(static fn (array $row): int => $row['generation'], $reader->listManifests(10)),
            'and an object that is not a manifest under this passphrase is not a readable generation at all',
        );
    }

    /**
     * The same injection into the NEWEST slot, where the class's own rule is
     * to fail LOUD rather than paper over the fault. Either outcome is a
     * refusal — the assertion is the security property itself: the injected
     * generation never becomes the ordering key, and a forged object is
     * never silently promoted by demoting a real one.
     */
    public function test_a_tampered_generation_at_the_newest_slot_is_never_promoted(): void
    {
        $gateway = new FakeS3Gateway;
        $passphrase = 'a passphrase with real entropy';

        $writer = new S3DurableVault('set-a', $gateway, $passphrase);
        $writer->putManifest((string) json_encode(['generation' => 5, 'files' => []]));

        $gateway->put(
            'teleframe-backup/set-a/manifests/'.str_pad('2', 16, '0', STR_PAD_LEFT),
            (string) json_encode(['generation' => PHP_INT_MAX, 'files' => []]),
        );

        $reader = new S3DurableVault('set-a', $gateway, $passphrase);

        $seen = null;
        try {
            $seen = $reader->getLatestManifest()['generation'] ?? null;
        } catch (\RuntimeException) {
            $seen = 'refused';
        }

        self::assertNotSame(
            PHP_INT_MAX,
            $seen,
            'the injected generation must never become "latest", by any path',
        );
    }

    // ── CYCLE 39 GOAL E: putManifest must not lose a generation ────────────

    /**
     * THE RACE, WITH A DOUBLE THAT CAN ACTUALLY PRODUCE IT. `FakeS3Gateway`
     * has no interleaving hook, so a concurrency test written against it
     * passes VACUOUSLY — that is exactly why the pre-fix suite could not see
     * this bug. `InterleavingS3Gateway` fires a rival writer from inside
     * another writer's own list→write window, at the sequence that writer
     * just chose, which is what a second `teleframe:backup` run looks like
     * when both processes listed the bucket before either wrote.
     *
     * The rival lands AFTER our listing (so our listing is stale and points
     * at a slot that is no longer free) and BEFORE our write, which is the
     * window a free-slot re-read closes: the old code wrote over a live
     * manifest and that writer's whole generation was destroyed, silently,
     * with both runs reporting success.
     *
     * The assertion in both directions is the same and is the one that
     * matters: a real backup run's manifest must still be there afterwards.
     */
    public function test_a_concurrent_manifest_writer_cannot_destroy_our_generation(): void
    {
        $gateway = new InterleavingS3Gateway;
        $vault = new S3DurableVault('set-a', $gateway);

        $vault->putManifest((string) json_encode(['generation' => 1, 'files' => []]));

        // The rival, writing generation 9 from another host, into the very
        // slot we are about to choose — with a listing that predates us.
        $rival = (string) json_encode(['generation' => 9, 'files' => []]);
        $gateway->onManifestGet(function (int $sequence) use ($gateway, $rival): void {
            $gateway->writeManifestRaw($sequence, $rival);
        });
        $ours = (string) json_encode(['generation' => 2, 'files' => []]);
        $ourSlot = $vault->putManifest($ours);

        $stored = [];
        foreach ($gateway->listAsMap() as $key => $bytes) {
            if (str_contains($key, '/manifests/')) {
                $stored[] = $bytes;
            }
        }

        self::assertContains(
            $rival,
            $stored,
            "the rival's manifest must survive ours — neither run may erase the other",
        );
        self::assertContains(
            $ours,
            $stored,
            'and our own generation must be published rather than dropped',
        );
        self::assertSame(3, (int) $ourSlot, 'we skipped the occupied slot instead of writing over it');
    }

    /**
     * The same race from the other side: the rival lands AFTER our put, so our
     * own bytes are the ones that get replaced. Claiming the slot means
     * verifying it, not assuming it.
     */
    public function test_a_manifest_writer_yields_the_slot_it_actually_lost(): void
    {
        $gateway = new InterleavingS3Gateway;
        $vault = new S3DurableVault('set-a', $gateway);

        $gateway->onManifestPut(function (int $sequence) use ($gateway): void {
            $gateway->writeManifestRaw($sequence, (string) json_encode(['generation' => 9, 'files' => []]));
        });
        $ours = (string) json_encode(['generation' => 2, 'files' => []]);
        $ourSlot = $vault->putManifest($ours);

        self::assertSame(2, (int) $ourSlot, 'the lost slot is skipped, not reused');
        self::assertContains($ours, array_values($gateway->listAsMap()), 'our manifest is re-published one slot on');

        // A reader sees three manifests, newest generation first, with nothing
        // silently missing from the middle.
        $reader = new S3DurableVault('set-a', $gateway);
        self::assertSame(
            [9, 2],
            array_map(
                static fn (array $row): int => $row['generation'],
                $reader->listManifests(10),
            ),
        );
    }
}

/**
 * A gateway double that can RUN A SECOND WRITER MID-FLIGHT — the one thing
 * `FakeS3Gateway` structurally cannot do, and the reason the pre-fix suite
 * had no concurrency coverage at all.
 *
 * It shares one flat key => bytes store, and fires a hook from inside
 * `put()` (after) or just before it (before), handing the hook the SEQUENCE
 * the writer under test just chose. That is the real race: two processes
 * that both listed the bucket before either of them wrote, so the rival's
 * choice of slot is a stale but entirely legitimate one.
 */
final class InterleavingS3Gateway implements S3GatewayContract
{
    /** @var array<string, string> */
    private array $objects = [];

    /** @var (callable(int): void)|null */
    private $afterPut;

    /** @var (callable(int): void)|null */
    private $onGet;

    public function onManifestPut(callable $hook): void
    {
        $this->afterPut = $hook;
    }

    /**
     * Fire the hook inside the first get() of a manifest slot — i.e. between
     * the writer's list() and its write(), which is precisely the window
     * `putManifest` has to defend.
     */
    public function onManifestGet(callable $hook): void
    {
        $this->onGet = $hook;
    }

    /** A rival writer's raw object write, straight into the store. */
    public function writeManifestRaw(int $sequence, string $bytes): void
    {
        $this->objects[$this->manifestKeyFor($sequence)] = $bytes;
    }

    public function get(string $key): string|false
    {
        $sequence = $this->sequenceIn($key);
        $hook = $this->onGet;
        if ($sequence !== null && $hook !== null) {
            $this->onGet = null; // one shot: a hook that re-armed would recurse
            $hook($sequence);
        }

        return $this->objects[$key] ?? false;
    }

    public function put(string $key, string $bytes): void
    {
        $this->objects[$key] = $bytes;

        $sequence = $this->sequenceIn($key);
        $hook = $this->afterPut;
        if ($sequence !== null && $hook !== null) {
            $this->afterPut = null;
            $hook($sequence);
        }
    }

    public function delete(string $key): void
    {
        unset($this->objects[$key]);
    }

    public function list(): array
    {
        $found = [];
        foreach ($this->objects as $key => $bytes) {
            $found[] = ['key' => $key, 'size' => strlen($bytes)];
        }

        return $found;
    }

    /**
     * @return array<string, string>
     */
    public function listAsMap(): array
    {
        return $this->objects;
    }

    private function manifestKeyFor(int $sequence): string
    {
        return 'teleframe-backup/set-a/manifests/'.str_pad((string) $sequence, 16, '0', STR_PAD_LEFT);
    }

    private function sequenceIn(string $key): ?int
    {
        if (! str_contains($key, '/manifests/')) {
            return null;
        }

        $bare = substr($key, (int) strrpos($key, '/') + 1);
        if ($bare === '' || strspn($bare, '0123456789') !== strlen($bare)) {
            return null;
        }

        return (int) $bare;
    }
}