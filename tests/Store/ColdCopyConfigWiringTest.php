<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;

/**
 * THE WIRING, END TO END (V1.2 cold-copy posture, 2026-09-27).
 *
 * The gap this file closes: `teleframe.tstore.reference_policy` was wired into
 * the StoreService ctor, and every test that proved the posture works injected
 * `ReferencePolicy::coldRemoteAuthoritative()` as a constructor argument. The
 * two halves were therefore each proven and their MEETING was not: nothing
 * asserted that the string an operator puts in config/env is the string the
 * service ends up carrying, and nothing asserted that the service the
 * CONTAINER hands out behaves cold.
 *
 * So this file starts where an operator starts — at the value — and walks the
 * real chain: env → merged config → the provider's own singleton closure →
 * a StoreService resolved out of the container → a read served with no local
 * index row and no local file, and a real file actually unlinked from a real
 * temp file. Every link is a shipped link:
 *
 *  - the provider is booted by `Tests\Schema\TestCase` (Orchestra Testbench),
 *    so `config('teleframe.tstore.reference_policy')` is the merged file;
 *  - `TstoreCommand::SERVICE_KEY` is deliberately NOT bound here. The service
 *    under test is the one the provider's own closure builds, which is the only
 *    place `ReferencePolicy::fromString()` is called in the whole package. Bind
 *    the key with a hand-made StoreService — as every other tstore test does —
 *    and this file would assert nothing about the wiring at all, which is
 *    exactly the shape of the gap it exists to close.
 *
 * WHAT IS STILL FAKED, and the line is drawn at the account scope: the four
 * tstore PORTS. `CHANNEL_FACTORY_KEY` needs a live UserAccountScope (a session
 * string, a network, a Telegram account), so it is bound to a FakeStoreApi
 * channel factory. The POLICY, the config read, the closure and the object the
 * closure returns are all real. No live Telegram run exists in this repo and
 * none is claimed here.
 *
 * The DEFAULT arm is not decoration: `retain-local` staying inert is the
 * recorded ruling (see ReferencePolicy's docblock), and this file is the only
 * place that ruling is proven at the level an operator meets it — the value
 * they would have to change.
 *
 * AND, SINCE THE PROOF BECAME CONTENT-SHAPED (2026-09-27), this file also
 * carries the arm that matters most at this level: the cold posture refusing a
 * local file whose bytes are not the document's. The reclaim fixtures here
 * derive the wire document FROM the local temp file, so the pairing under test
 * is stated rather than assumed, and the negative test differs from the
 * positive one by nothing except whose bytes they are.
 */
final class ColdCopyConfigWiringTest extends TestCase
{
    private const ACCOUNT = 7;

    private const BUCKET = 'data:wiring';

    private const POLICY_ENV = 'TELEFRAME_REFERENCE_POLICY';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    /** A real local file the reclaim really unlinks. */
    private string $path = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FakeStoreApi(scope: 'cold-wiring');
        $this->index = new MemoryStoreIndex;
        $this->path = (string) tempnam(sys_get_temp_dir(), 'tfstore-wiring-');
        file_put_contents($this->path, "a local file the operator wants off their disk\n");

        // Only the ports. The channel factory is the one that needs an account
        // scope; index/cache/queue are bound explicitly so the test can INSPECT
        // the index the provider-built service resolved through (the cache and
        // queue the provider builds by default are exactly the fakes the
        // config selects, so overriding them adds nothing but a lie).
        $this->app->bind(TstoreCommand::CHANNEL_FACTORY_KEY, fn (): callable => fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()));
        // The provider hands the ctor `teleframe.tstore.account_id`, so the
        // service the container builds is only the one these fixtures describe
        // if that key says which account the rows belong to. Left at 0 the
        // container service would index under account 0 while the assertions
        // read account 7 — a test that would pass for the wrong reason.
        config()->set('teleframe.tstore.account_id', self::ACCOUNT);
        $this->app->instance(TstoreCommand::INDEX_KEY, $this->index);
        $this->app->instance(TstoreCommand::HOT_CACHE_KEY, new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024));
        $this->app->instance(TstoreCommand::QUEUE_KEY, new SyncUploadQueue);
    }

    protected function tearDown(): void
    {
        if ($this->path !== '' && is_file($this->path)) {
            unlink($this->path);
        }

        putenv(self::POLICY_ENV);
        unset($_ENV[self::POLICY_ENV], $_SERVER[self::POLICY_ENV]);

        parent::tearDown();
    }

    /**
     * The service the CONTAINER hands out — never a hand-made one. If the
     * provider's closure stopped reading the config key, or read a different
     * key, or ignored fromString(), every assertion in this file would fail.
     */
    private function containerService(): StoreService
    {
        $service = $this->app->make(TstoreCommand::SERVICE_KEY);

        self::assertInstanceOf(StoreService::class, $service);

        return $service;
    }

    /** A warm service over a THROWAWAY index, so the provider's index stays empty. */
    private function seedOnTheWire(string $name, string $bytes): void
    {
        (new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024),
            queue: new SyncUploadQueue,
        ))->put(self::BUCKET, $name, $bytes);
    }

    /**
     * File the fake wire's direct-seed helper (uploadDocument/sendText, which
     * take no peer) into THIS bucket's own channel partition, so a document
     * seeded by hand lands where the container-built channel actually reads.
     */
    private function seedDocumentsInTheBucketChannel(): void
    {
        $channel = new TelegramStoreChannel(self::BUCKET, $this->api->apiMap());

        $this->api->seedPeerId = (int) ($channel->bucketInputPeer()['channel_id'] ?? 0);
    }

    /** The bytes on the host disk right now — precisely what a reclaim frees. */
    private function localFileBytes(): string
    {
        self::assertFileExists($this->path, 'fixture precondition: the local file exists');

        return (string) file_get_contents($this->path);
    }

    /**
     * Seed a wire document whose bytes ARE the local file's, read off it rather
     * than retyped. A content-shaped proof is only as honest as this pairing:
     * seeding a document and writing a local file as two unrelated literals is
     * how this file used to assert nothing (it shipped 'jpeg bytes' against an
     * unrelated file and called it a proof of durability), so the pairing is
     * now DERIVED and asserted below in every test that reclaims.
     */
    private function seedTheLocalFileAsADocument(string $name): int
    {
        $this->seedDocumentsInTheBucketChannel();

        return $this->api->uploadDocument($name, $this->localFileBytes());
    }

    /**
     * Seed a wire document holding $bytes, which the caller must make genuinely
     * different from the local file's. This is the stranger's document: a
     * perfectly addressable message that simply is not this file.
     */
    private function seedAStrangerDocument(string $name, string $bytes): int
    {
        $this->seedDocumentsInTheBucketChannel();

        return $this->api->uploadDocument($name, $bytes);
    }

    /**
     * The bytes the wire holds for $msgId — i.e. what durableMediaFor() will
     * download and hash. Read back from the log rather than from the argument
     * that seeded it, so this asserts the SERVED bytes, not the caller's
     * memory of them.
     */
    private function wireBytesFor(int $msgId): string
    {
        foreach ($this->api->messages as $message) {
            if ((int) $message['id'] === $msgId) {
                return (string) $message['bytes'];
            }
        }

        self::fail('fixture precondition: the wire has no message for id '.$msgId);
    }

    // ── the chain, one link per assertion ─────────────────────────────

    public function test_the_shipped_default_config_builds_a_retaining_store_service(): void
    {
        // No config set, no env set. This is what every host gets on install.
        self::assertSame(ReferencePolicy::RETAIN_LOCAL, config('teleframe.tstore.reference_policy'));
        self::assertSame(ReferencePolicy::RETAIN_LOCAL, $this->containerService()->referencePolicy()->mode());
    }

    public function test_the_env_var_reaches_the_merged_config(): void
    {
        // Link 1 of the chain, isolated: the shipped config file reads this
        // exact env name for this exact key. If the key were renamed or the env
        // name drifted, this fails before the provider is even involved.
        $_ENV[self::POLICY_ENV] = ReferencePolicy::COLD_REMOTE_AUTHORITATIVE;
        $_SERVER[self::POLICY_ENV] = ReferencePolicy::COLD_REMOTE_AUTHORITATIVE;
        putenv(self::POLICY_ENV.'='.ReferencePolicy::COLD_REMOTE_AUTHORITATIVE);

        $reloaded = require dirname(__DIR__, 2).'/src/Laravel/config/teleframe.php';

        self::assertSame(
            ReferencePolicy::COLD_REMOTE_AUTHORITATIVE,
            $reloaded['tstore']['reference_policy'],
            'the config key is not read from the env var the docs name',
        );
    }

    public function test_config_cold_reaches_the_container_service_as_a_cold_posture(): void
    {
        config()->set('teleframe.tstore.reference_policy', ReferencePolicy::COLD_REMOTE_AUTHORITATIVE);
        $this->app->forgetInstance(TstoreCommand::SERVICE_KEY);

        $policy = $this->containerService()->referencePolicy();

        self::assertSame(ReferencePolicy::COLD_REMOTE_AUTHORITATIVE, $policy->mode());
        self::assertTrue($policy->trustsRemote());
        self::assertTrue($policy->reclaims());
        self::assertSame(ReferencePolicy::PROOF_REMOTE_DOCUMENT, $policy->durabilityProof());
    }

    public function test_an_unrecognised_policy_refuses_loudly_by_name_instead_of_retaining(): void
    {
        config()->set('teleframe.tstore.reference_policy', 'cold');
        $this->app->forgetInstance(TstoreCommand::SERVICE_KEY);

        // A silent fall back to retain-local would hide a typo from an operator
        // who believes it is reclaiming disk. The refusal must happen at
        // RESOLVE time — that is when the provider's closure runs.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown reference policy "cold"');

        $this->containerService();
    }

    // ── and the cold behaviour itself, through the container-built service ──

    public function test_a_config_cold_service_reads_an_object_with_no_local_row_and_no_local_file(): void
    {
        $bytes = str_repeat('cold bytes from telegram; ', 500);
        $this->seedOnTheWire('wiring.bin', $bytes);

        // A local file, so the test can then make the "no local file" claim
        // honestly: the copy that exists locally is NOT the object being read,
        // and it is unlinked before the read. The bytes below are served with
        // nothing on this machine that holds them.
        file_put_contents($this->path, 'a different local file entirely');
        unlink($this->path);

        config()->set('teleframe.tstore.reference_policy', ReferencePolicy::COLD_REMOTE_AUTHORITATIVE);
        $this->app->forgetInstance(TstoreCommand::SERVICE_KEY);
        $cold = $this->containerService();

        self::assertNull($this->index->findObject(self::ACCOUNT, self::BUCKET, 'wiring.bin'), 'precondition: no local index row');
        self::assertSame([], $this->index->listObjects(self::ACCOUNT, self::BUCKET), 'precondition: the local index knows nothing');
        self::assertFalse(is_file($this->path), 'precondition: no local file anywhere near this read');

        self::assertSame($bytes, $cold->get(self::BUCKET, 'wiring.bin'), 'the read was served out of the remote tier');
        self::assertTrue($cold->exists(self::BUCKET, 'wiring.bin'), 'and exists() agrees, which is the bucket semantics');
        self::assertNotNull(
            $this->index->findObject(self::ACCOUNT, self::BUCKET, 'wiring.bin'),
            'the remote answer was written back as a cache, so the next read is local',
        );
    }

    /**
     * THE CENTREPIECE: the shipped default frees nothing, and the config-driven
     * cold posture frees a real file off a real temp file — through the service
     * the CONTAINER built, with no policy argument anywhere in the call, so the
     * posture under test is genuinely the one an operator configures.
     *
     * What it pins beyond the two arms: that the reclaim is safe rather than
     * merely effective. The document on the wire is seeded FROM the local file,
     * so the proof has nothing to stand on except the two being the same bytes;
     * there is no local truth row at all, so the cold posture has to resolve the
     * reference itself and hash the document it downloads; and the deleter is
     * handed the sha256 that was just verified, which is the only seam where the
     * final TOCTOU hop can still be closed.
     *
     * Falsified: with the local file replaced by bytes that are NOT the
     * document's, the cold arm comes back `refused / content-mismatch` with the
     * deleter untouched — see
     * test_a_config_cold_service_refuses_to_free_a_file_the_document_never_held.
     * Before the content-shaped proof the same fixture paired 'jpeg bytes' with
     * an unrelated local file and went green on it, which is the hole this file
     * used to certify.
     */
    public function test_a_config_cold_service_really_frees_a_local_file_and_the_default_one_never_does(): void
    {
        // A document already in the bucket channel, seeded FROM the local file
        // so the reclaim below is freed against its OWN bytes. The index is
        // emptied afterwards on purpose: no local media row, so nothing local
        // can stand in for the reference and the cold path must do the work.
        $msgId = $this->seedTheLocalFileAsADocument('wiring.jpg');
        $this->index->deleteBucket(self::ACCOUNT, self::BUCKET);

        $localBytes = $this->localFileBytes();

        self::assertGreaterThan(0, $msgId);
        self::assertTrue(is_file($this->path), 'precondition: the local copy exists');
        self::assertSame(
            $localBytes,
            $this->wireBytesFor($msgId),
            'precondition: the document the proof will download IS the file on disk, byte for byte — '
            .'a reclaim of a mismatched pair is a different test, and it is one of the others here',
        );
        self::assertNull(
            $this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId),
            'precondition: no local truth row, so the cold posture resolves the reference itself',
        );

        // A three-parameter deleter, deliberately: it records the hash the
        // service claims to have verified, which is asserted below. The
        // republish test keeps a two-parameter closure, so both arities are
        // pinned at this level.
        $deleted = [];
        $verified = [];
        $unlink = function (string $disk, string $path, string $expectedHash) use (&$deleted, &$verified): bool {
            $deleted[] = [$disk, $path];
            $verified[] = $expectedHash;

            return is_file($path) && unlink($path);
        };

        // 1. The SHIPPED DEFAULT: config untouched. It must not even look.
        $retained = $this->containerService()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $unlink,
        );

        self::assertTrue($retained->isRetained());
        self::assertSame(ReclaimOutcome::REASON_POLICY_RETAINS, $retained->reason());
        self::assertSame([], $deleted, 'the default posture must not reach the deleter');
        self::assertTrue(is_file($this->path), 'and the bytes must still be on disk');
        self::assertSame([], $verified, 'nor hand it a hash to act on');
        self::assertSame([], $this->api->searches, 'nor pay a wire search for a posture that reclaims nothing');

        // 2. The OPT-IN, chosen by config alone — no policy argument, so the
        //    posture really is the service's, not the caller's.
        config()->set('teleframe.tstore.reference_policy', ReferencePolicy::COLD_REMOTE_AUTHORITATIVE);
        $this->app->forgetInstance(TstoreCommand::SERVICE_KEY);

        $reclaimed = $this->containerService()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $unlink,
        );

        self::assertTrue($reclaimed->freedBytes(), implode(' ', [$reclaimed->state(), $reclaimed->reason()]));
        self::assertSame(ReclaimOutcome::REASON_DELETED, $reclaimed->reason());
        self::assertSame([['local', $this->path]], $deleted);
        self::assertFalse(is_file($this->path), 'the local bytes are actually gone from the disk');
        self::assertSame(
            [hash('sha256', $localBytes)],
            $verified,
            'the deleter was handed exactly the sha256 of the bytes it unlinked',
        );
        self::assertSame(0, $this->api->uploadPartCalls, 'and proving it durable needed no bytes uploaded — '
            .'the content proof DOWNLOADS the document, it never re-uploads it');
    }

    /**
     * THE NEGATIVE ARM, and the one an operator actually meets: a local file
     * that the document never held must survive, whatever the config says.
     *
     * The threat this pins: the config is the ONLY thing standing between an
     * operator and a colder host, and before the content-shaped proof the proof
     * it switched on never mentioned the file at all — a non-empty
     * file_reference plus a positive doc_id, i.e. "document 777 is
     * addressable". That sentence is true of a stranger's document. So a host
     * running exactly the wiring the operator asked for, with a perfectly good
     * document in its data channel, would free a local file that document has
     * no relationship to. The whole chain is exercised here: the same
     * `teleframe.tstore.reference_policy` value, the same provider closure, the
     * same container-built service, the same message id and the same bucket —
     * the ONLY difference from the centrepiece is whose bytes they are.
     *
     * Falsified two ways. (1) Overwriting the local file with the stranger's
     * bytes turns the test red on the reason token: the outcome is
     * `deleted`, the deleter is called and the file is gone — so the refusal is
     * bound to the bytes, not to the posture, the config or the message id.
     * (2) Removing the two assertions that the deleter was never called and that
     * the file is still readable leaves the test green while the operator's
     * disk is emptied, so those two carry the property; the token alone only
     * carries the diagnosis.
     */
    public function test_a_config_cold_service_refuses_to_free_a_file_the_document_never_held(): void
    {
        // A document that is addressable, in the right bucket, under a real
        // message id — and holds bytes the local file has never seen.
        $strangerBytes = 'bytes telegram holds for a document this host never wrote';
        $msgId = $this->seedAStrangerDocument('stranger.jpg', $strangerBytes);
        $this->index->deleteBucket(self::ACCOUNT, self::BUCKET);

        $localBytes = $this->localFileBytes();

        self::assertNotSame($strangerBytes, $localBytes, 'precondition: the pairing really is a mismatch');
        self::assertSame(
            $strangerBytes,
            $this->wireBytesFor($msgId),
            'precondition: the document the proof will download is the stranger\'s, not this file',
        );
        self::assertNull(
            $this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId),
            'precondition: no local truth row, so the refusal is not a cached row defending itself',
        );

        config()->set('teleframe.tstore.reference_policy', ReferencePolicy::COLD_REMOTE_AUTHORITATIVE);
        $this->app->forgetInstance(TstoreCommand::SERVICE_KEY);

        $deleted = [];
        $refused = $this->containerService()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            function (string $disk, string $path) use (&$deleted): bool {
                $deleted[] = [$disk, $path];

                return is_file($path) && unlink($path);
            },
        );

        // The TOKEN, not just the state: `refused` alone would also be what an
        // unreachable document returns, and the operator's question is "is my
        // file still the only copy?". A content mismatch says the proof was
        // built about different bytes, which is the diagnosis.
        self::assertSame(ReclaimOutcome::REFUSED, $refused->state());
        self::assertSame(ReclaimOutcome::REASON_CONTENT_MISMATCH, $refused->reason());
        self::assertFalse($refused->freedBytes());
        self::assertSame([], $deleted, 'the deleter is unreachable: the proof never got past the bytes');
        self::assertTrue(is_file($this->path), 'and the bytes are still on the host disk');
        self::assertSame($localBytes, $this->localFileBytes(), 'untouched — not truncated, not rewritten');
        self::assertSame(
            0,
            $this->api->uploadPartCalls,
            'the refusal cost a download and no upload: the proof reads, it never writes',
        );
    }

    /**
     * THE LOAD-BEARING ROUND TRIP: a file freed by the config-driven cold
     * posture, and then the document that proved it durable re-published to
     * another peer BY HASH. This is the reason the reclaim is worth its cost —
     * "delete it locally, it's still in Telegram" is only useful if the
     * "still in Telegram" half can actually put the bytes back on a wire.
     *
     * It also pins the arity compatibility in the other direction from the
     * centrepiece: the deleter here declares TWO parameters, because the
     * verified-hash third argument is additive and a two-parameter closure must
     * keep working unchanged. A closure that did not tolerate the extra
     * argument would break every existing call site in src/ at the moment the
     * proof was tightened.
     *
     * `uploadPartCalls === 0` across BOTH halves is the assertion that makes
     * this a reclaim and a re-publish rather than a silent re-upload: the
     * content proof downloads the document, the publish hands Telegram its own
     * hash, and the bytes never travel up.
     *
     * Falsified: pointing publishByReference() at a message id with no media
     * row goes red on the loud RuntimeException, so the reference the reclaim
     * left behind really is what carried the publish.
     */
    public function test_the_provider_built_service_publishes_by_hash_after_a_config_driven_reclaim(): void
    {
        $msgId = $this->seedTheLocalFileAsADocument('republished.jpg');
        $this->index->deleteBucket(self::ACCOUNT, self::BUCKET);

        $localBytes = $this->localFileBytes();
        self::assertSame(
            $localBytes,
            $this->wireBytesFor($msgId),
            'precondition: the document the reclaim will download is this file, byte for byte — otherwise '
            .'this round trip is really the refusal case, and that is a different test',
        );

        config()->set('teleframe.tstore.reference_policy', ReferencePolicy::COLD_REMOTE_AUTHORITATIVE);
        $this->app->forgetInstance(TstoreCommand::SERVICE_KEY);
        $cold = $this->containerService();

        $reclaimed = $cold->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            // Two parameters on purpose — see this test's docblock.
            fn (string $disk, string $path): bool => is_file($path) && unlink($path),
        );

        self::assertTrue($reclaimed->freedBytes(), implode(' ', [$reclaimed->state(), $reclaimed->reason()]));
        self::assertFalse(is_file($this->path));

        $posted = $cold->publishByReference(
            self::BUCKET,
            $msgId,
            ['_' => 'inputPeerChannel', 'channel_id' => 900, 'access_hash' => 4242],
            'after the config-driven reclaim',
        );

        self::assertGreaterThan(0, $posted);
        self::assertSame(0, $this->api->uploadPartCalls, 'a hash-addressed publish never re-uploads');
        self::assertCount(1, $this->api->referenceSends);

        // What carried the publish. The index was emptied, so this row was
        // written back by the RECLAIM's own freshness consult — the reclaim
        // did not merely free the file, it left behind the reference the
        // re-publish is built on.
        $recovered = $this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId);
        self::assertNotNull($recovered, 'the reclaim wrote the reference it proved back into the index');
        self::assertGreaterThan(0, (int) $recovered['doc_id']);
        self::assertSame(
            (int) $recovered['doc_id'],
            (int) ($this->api->referenceSends[0]['media']['id']['id'] ?? 0),
            'the re-publish hands Telegram the document the reclaim proved durable — a hash, not a fresh upload',
        );
    }
}
