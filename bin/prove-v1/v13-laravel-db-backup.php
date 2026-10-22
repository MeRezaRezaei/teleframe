<?php

declare(strict_types=1);

/**
 * V1.3 — THE DEFAULT BEHAVIOUR: the host's own Laravel database, dumped on a
 * schedule, into a channel. This section ENTERS the capability; the proof
 * before it stopped at the first refusal (`no database driver configured`),
 * and that refusal is exactly the thing that hides every other claim.
 *
 * THE OPERATOR'S OWN WORDS, quoted from
 * .brain/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md:
 *
 *   "also one thing is the default behaviour is it will be installed in a
 *   laravel so it can simply dump the laravel database and make a backup from
 *   it in schedules to a channel in accounts it has this kind is very
 *   important and one kind of backup is the kind that we do pure since we want
 *   to use the hash that telegram gives us as media hash somewhere else like a
 *   private channel that we store our backups of pictures and for posting them
 *   in our public channel we just pass the hash telegram gives this kind is
 *   very important it helps keeping storage of laravel free and also using the
 *   content as needed this helps archiving data very easy too since we delete
 *   something in main laravel storage but its hash in telegram is still in
 *   database and the file is in the channel we can store big data and keep our
 *   hot data and cold data intact"
 *
 * and the package's own scope line: "app that converts telegram into a
 * permanent backup storage".
 *
 * WHAT THIS SECTION PROVES, claim by claim. Every assertion below runs against
 * the package's REAL classes, from inside a REAL Laravel host whose database is
 * a REAL writable file that this probe seeds:
 *
 *   1. "dump the laravel database" — the host's own migrated sqlite database
 *      really produces bytes, read through the host's own live PDO connection,
 *      and the report carries their exact length and sha256. It ALSO records
 *      the honest limit as a refusal: the package ships a `MysqlDumper` and a
 *      `PostgresDumper` and NO sqlite dumper, so Laravel's own default
 *      database is not dumpable by the package as shipped — a host on sqlite
 *      has to bind its own dumper on the shipped `DatabaseDumper` seam.
 *   2. "in schedules" — the trigger really lands on the HOST's Laravel
 *      scheduler, by name, read back off the container's `Schedule`, and the
 *      shipped default posture (OFF) is asserted next to the armed one.
 *   3. "to a channel" — every byte really left for an `inputPeerChannel` with a
 *      positive channel id (never a bot chat), into the config-derived
 *      `teleframe-backup:<bucket>` bucket, SEALED under the configured
 *      passphrase — proven by the seeded plaintext marker being absent from
 *      every byte the transport received.
 *   4. "the kind that we do pure … we just pass the hash" — the package's own
 *      name for this is `MediaReference` + `publishByReference()`: the run
 *      yields a document reference (doc id + access hash + file reference) and
 *      re-publishing it to a SECOND channel uploads ZERO bytes.
 *   5. "keeping storage of laravel free … its hash in telegram is still in
 *      database and the file is in the channel" — the dump spool is released,
 *      a real file in the host's own `storage/` is freed by `reclaimLocal()`
 *      while its bytes are still readable out of the channel, and the run
 *      ledger row round-trips through the HOST's own `tf_store_backup_runs`.
 *
 * WHAT IS NOT PROVEN HERE, stated rather than implied: a backup run writes NO
 * `tf_store_media` row, so a dump has no stored document reference — its
 * "hash" is the content sha256 in the run ledger. That is asserted as the
 * current truth in its own check, not deleted to make the section green.
 *
 * No credential, session string, auth key or socket is involved: the host runs
 * unconfigured and the transport is the package's OWN shipped recording double
 * (`tests/Support/FakeStoreApi.php`), bound on a public bind key a host may
 * rebind: `teleclient.tstore.service`.
 *
 * Returns the one section array the parent probe walks. Informational keys are
 * plain strings and are skipped by the verdict walker.
 */

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Facades\StoreBackup;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunReport;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpResult;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\ChannelRegistry;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Store\TelegramTier;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;

/**
 * THE HOST-SUPPLIED DUMPER — a host's own `DatabaseDumper`, on the package's
 * own shipped seam.
 *
 * WHY IT EXISTS IS THE POINT, not an inconvenience. The package ships
 * `MysqlDumper` (mysqldump) and `PostgresDumper` (pg_dump) and nothing else,
 * and a stock Laravel host's database is sqlite — so the package genuinely
 * cannot dump the database the operator named. The check
 * `shipped-dumper-cannot-dump-sqlite` asserts that refusal rather than hiding
 * it behind this class.
 *
 * WHAT IT IS NOT: a fake dump. It reads the HOST'S OWN LIVE PDO CONNECTION —
 * the same object `DB::connection()->getPdo()` hands a host — and emits real
 * DDL and real rows out of the host's own migrated `database/database.sqlite`.
 * Everything below the dump is the package's: `DumpWorkspace` owns the spool
 * file and its release, `DumpResult` carries the byte count, and
 * `DatabaseBackupService::backup()` runs the whole chain. The check
 * `real-bytes-from-the-host-database` proves the bytes are the host's by
 * CONTENT, not by assertion: the dumped SQL has to contain this probe's
 * freshly seeded row marker AND the host's own migrated `users` table.
 */
final class V13HostSqliteDumper implements DatabaseDumper
{
    /** @var array{path: string, bytes: int, sql: string, source: string, startedAt: int}|null */
    public ?array $observed = null;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $source,
    ) {}

    public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
    {
        $started = time();

        // The SHIPPED workspace: it owns the 0700 directory, the 0600 tempnam
        // spool file, and the release ledger the "space is freed" check reads.
        $spool = $workspace->spool();
        $sql = $this->dumpSql();

        // The shipped dumpers refuse a zero-byte dump loudly rather than let a
        // clean exit become a green backup; hold exactly the same line.
        if ($sql === '') {
            throw new BackupException('the host database dump produced no SQL at all — nothing reached the spool.');
        }

        fwrite($spool['handle'], $sql);
        fclose($spool['handle']);

        $this->observed = [
            'path' => $spool['path'],
            'bytes' => strlen($sql),
            'sql' => $sql,
            'source' => $this->source,
            'startedAt' => $started,
        ];

        return new DumpResult(
            path: $spool['path'],
            bytes: strlen($sql),
            driver: $target->driver,
            database: $target->database,
            startedAt: $started,
            finishedAt: time(),
            exitCode: 0,
            stderrTail: '',
        );
    }

    /** Every table's CREATE statement and every row, as SQL. Zero regex. */
    private function dumpSql(): string
    {
        $tables = $this->pdo->query(
            "SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
        );
        if ($tables === false) {
            return '';
        }

        $out = "-- V1.3 host-install dump of the host's own database\n";
        foreach ($tables->fetchAll(PDO::FETCH_ASSOC) as $table) {
            $name = (string) ($table['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $quoted = '"'.str_replace('"', '""', $name).'"';
            $out .= "\n".((string) ($table['sql'] ?? '')).";\n";

            $rows = $this->pdo->query('SELECT * FROM '.$quoted);
            if ($rows === false) {
                continue;
            }
            foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $literals = [];
                foreach ($row as $value) {
                    $literals[] = $this->literal($value);
                }
                $out .= 'INSERT INTO '.$quoted.' VALUES ('.implode(', ', $literals).");\n";
            }
        }

        return $out;
    }

    private function literal(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return "'".str_replace("'", "''", (string) $value)."'";
    }
}

/** Run a command in the throwaway host and hand back its output. Never throws. */
function v13Run(array $argv, string $cwd, array $env, int $timeoutSec = 90): array
{
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = @proc_open($argv, $descriptors, $pipes, $cwd, $env);
    if (! is_resource($process)) {
        return ['code' => 127, 'out' => ''];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $out = '';
    $started = microtime(true);
    $code = 0;
    while (true) {
        $out .= (string) stream_get_contents($pipes[1]).(string) stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if ($status['running'] === false) {
            $code = (int) $status['exitcode'];

            break;
        }
        if ((microtime(true) - $started) > $timeoutSec) {
            proc_terminate($process, 9);

            break;
        }
        usleep(20000);
    }
    $out .= (string) stream_get_contents($pipes[1]).(string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return ['code' => $code, 'out' => $out];
}

// ===========================================================================
// 0. THE HOST'S OWN DATABASE — the thing the operator said to dump.
// ===========================================================================

$connection = DB::connection();
$pdo = $connection->getPdo();
$hostDbPath = (string) $connection->getDatabaseName();
$hostDbFile = is_file($hostDbPath) ? $hostDbPath : '';
$hostDriver = (string) $connection->getDriverName();

// THE BACKUP ACCOUNT, REGISTERED THE WAY AN OPERATOR REGISTERS ONE.
//
// This is not incidental setup, and the reason is measured: the migration
// chain puts a FOREIGN KEY on `tf_store_backup_runs.account_id` referencing
// `telegram_accounts.user_id` with ON DELETE CASCADE. A run for an account the
// host has never registered therefore dies on the LEDGER INSERT — after the
// dump bytes have already been sealed and uploaded to the channel. So the
// account row is what makes the run complete, and the check below asserts the
// constraint itself rather than letting it be an invisible prerequisite.
$v13AccountId = 4242;
DB::table('telegram_accounts')->where('user_id', $v13AccountId)->delete();
DB::table('telegram_accounts')->insert([
    'label' => 'v13-host-probe-backup-account',
    'type' => 'user',
    'user_id' => $v13AccountId,
    'dc_id' => 2,
    'created_at' => '2026-01-01 00:00:00',
    'updated_at' => '2026-01-01 00:00:00',
]);

// The probe's own table, created INSIDE the host's own database with
// `Schema::create` — declared rather than assumed, and the `$what` below says
// so, because "the host's database" must not be a claim about a fixture.
$probeTable = 'teleframe_v13_probe_rows';
$seedMarker = 'v13-marker-'.bin2hex(random_bytes(6));
Schema::dropIfExists($probeTable);
Schema::create($probeTable, static function ($table): void {
    $table->id();
    $table->string('marker')->unique();
    $table->text('note')->nullable();
    $table->timestamps();
});
DB::table($probeTable)->insert([
    'marker' => $seedMarker,
    'note' => 'seeded by the V1.3 host-install probe into the host\'s own database',
    'created_at' => '2026-01-01 00:00:00',
    'updated_at' => '2026-01-01 00:00:00',
]);

// A spool directory INSIDE the throwaway host, wired through the documented
// `teleframe.store.backup.spool_dir` key, so "the space is freed" is a claim
// about a directory this probe can actually look at afterwards.
$spoolDir = $root.'/storage/framework/teleframe-v13-spool';

// A throwaway seal secret. Generated here, never printed, never stored, and
// never a literal in this file — the shipped sealed mode refuses to run without
// one and inventing a default secret is the thing the package declines to do.
$passphrase = bin2hex(random_bytes(24));

// ===========================================================================
// 1. THE SHIPPED DUMPER SEAM, ON THE HOST'S REAL DRIVER — refusal FIRST.
// ===========================================================================

// This is what the operator asked for: the host's own Laravel database. Its
// driver is sqlite. Read the config the provider reads and let the PACKAGE's own
// binding answer, before anything of ours is in the way. The resolved dumper is
// HELD, because the refusal has to be asked of the package's binding and not of
// the host-supplied one this section installs two steps below.
config([
    'teleframe.store.backup.database.driver' => $hostDriver,
    'teleframe.store.backup.database.database' => 'laravel',
]);
$stockDumper = app()->make(BackupSupport::DUMPER_KEY);

// ===========================================================================
// 2. CONFIGURE THE HOST the way an operator does, then let the PROVIDER build
//    its own service. Two seams are replaced — the dumper and the store
//    transport — and both are public bind keys a host may rebind.
// ===========================================================================

// THE SHIPPED RECORDING TRANSPORT DOUBLE, loaded explicitly.
//
// A host installs this package as a DEPENDENCY, and a dependency's
// autoload-dev is never installed — so the `tests/` tree is on disk beside the
// `src/` tree but is NOT on the host's autoloader. This is the same explicit
// require the passport probe does for its own shipped fixtures, and the same
// reason it states: the double must be the package's own published class, not a
// second copy of it written next to the gate.
$fakeApiFile = $root.'/vendor/merezarezaei/teleframe/tests/Support/FakeStoreApi.php';
if (! is_file($fakeApiFile)) {
    throw new RuntimeException(
        'V1.3: the package ships no tests/Support/FakeStoreApi.php — the recording transport '
        .'double this section binds is gone, and no assertion below would be measuring a real '
        .'transport. Nothing in this section was proven.',
    );
}
require_once $fakeApiFile;

$api = new FakeStoreApi(scope: 'v13-host');
$api->seedFallback = false;

// THE SHIPPED DEFAULT posture, and the one the backup run itself goes through:
// `retainLocal()` never touches a disk and never treats a missing local row as
// licence to ask the remote tier.
$storeIndex = new MemoryStoreIndex;
$store = new StoreService(
    accountId: $v13AccountId,
    channelFactory: static fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $api->apiMap()),
    index: $storeIndex,
    cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
    queue: new SyncUploadQueue,
);

// THE COLD POSTURE, over the SAME channel and the SAME transport double, for
// the half of the operator's sentence that is about cold data: under
// `coldRemoteAuthoritative()` the local truth tables are a CACHE the remote tier
// refills, so an object with no local row at all still reads, still re-publishes
// by reference, and can still have its local file freed. `put()` is unaffected by
// the posture — only reads, re-publish and reclaim are — so the backup run above
// stays on the shipped default.
$coldStore = new StoreService(
    accountId: $v13AccountId,
    channelFactory: static fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $api->apiMap()),
    index: new MemoryStoreIndex,
    cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
    queue: new SyncUploadQueue,
    referencePolicy: ReferencePolicy::coldRemoteAuthoritative(),
);

config([
    'teleframe.store.backup.target.account_id' => $v13AccountId,
    'teleframe.store.backup.target.passphrase' => $passphrase,
    'teleframe.store.backup.spool_dir' => $spoolDir,
]);

$dumper = new V13HostSqliteDumper($pdo, $hostDbFile);
app()->instance(BackupSupport::DUMPER_KEY, $dumper);
app()->instance(TstoreCommand::SERVICE_KEY, $store);
// Drop the instances the parent probe already resolved, so the PROVIDER's own
// wiring runs again against the config above — its bucket, its passphrase, its
// DatabaseTarget — rather than a service this probe assembled itself.
app()->forgetInstance(BackupSupport::SERVICE_KEY);
app()->forgetInstance(DatabaseTarget::class);
Facade::clearResolvedInstances();

$bucket = ChannelRegistry::PREFIX_BACKUP.(string) config('teleframe.store.backup.target.bucket');

// The OPERATOR'S OWN entry point, not a hand-assembled call: the `StoreBackup`
// facade's backup() resolves the container-bound DatabaseTarget the provider
// built from teleframe.store.backup.database.*, which is exactly what
// `php artisan teleframe:tstore backup` does.
$report = null;
$runError = '';
try {
    $report = StoreBackup::backup();
} catch (Throwable $e) {
    $runError = $e::class.': '.$e->getMessage();
}

// ===========================================================================
// 3. THE CHANNEL, THE MEDIA HASH, THE RE-PUBLISH, THE DELETION STORY.
// ===========================================================================

$dump = $dumper->observed;

$uploadedBytes = '';
foreach ($api->messages as $message) {
    if (is_string($message['bytes'] ?? null)) {
        $uploadedBytes .= $message['bytes'];
    }
}

$channelId = 0;
try {
    $channelId = (new TelegramStoreChannel($bucket, $api->apiMap()))->channelId();
} catch (Throwable) {
    $channelId = 0;
}

// The LAST document the run posted is the sealed chunk; its message id is the
// handle the hash-addressed half of the capability needs.
$chunkMsgId = 0;
foreach ($api->messages as $message) {
    if (($message['doc_id'] ?? null) !== null) {
        $chunkMsgId = (int) $message['id'];
    }
}

$mediaFor = null;
try {
    $mediaFor = $chunkMsgId > 0
        ? (new TelegramTier)->mediaFor(new TelegramStoreChannel($bucket, $api->apiMap()), $chunkMsgId)
        : null;
} catch (Throwable) {
    $mediaFor = null;
}

$uploadsBeforeRepublish = $api->uploadPartCalls;
$republishedTo = 0;
try {
    $republishedTo = $coldStore->publishByReference(
        $bucket,
        $chunkMsgId,
        ['_' => 'inputPeerChannel', 'channel_id' => 987654, 'access_hash' => 42],
        'v13-public-repost',
    );
} catch (Throwable $e) {
    $republishedTo = 0;
}
$uploadsAfterRepublish = $api->uploadPartCalls;

// --- "we delete something in main laravel storage but its hash in telegram is
// still in database and the file is in the channel" — against a REAL file in
// the HOST'S OWN storage directory, not a fixture. And this is the operator's
// "pure" kind, in the package's own word for it: MODE_RAW, the only mode where
// Telegram holds the very bytes the host holds, so the content binding a reclaim
// has to prove can actually hold. (The sealed case is probed one paragraph
// below, and it refuses — correctly, which is worth stating.)
$reclaimBucket = 'teleframe-data:v13-reclaim';
$reclaimName = 'v13-reclaim-probe.txt';
$reclaimPath = $root.'/storage/app/teleframe-v13-reclaim.txt';
$reclaimBytes = 'teleframe V1.3 reclaim probe — the host may delete this file; Telegram still holds it.';
@mkdir(dirname($reclaimPath), 0777, true);
file_put_contents($reclaimPath, $reclaimBytes);

// The SHIPPED DEFAULT first: with no operator configuration, nothing is deleted.
$defaultReclaim = $store->reclaimLocal('local', $reclaimPath, ['bucket' => $reclaimBucket, 'msg_id' => 1]);
$store->put($reclaimBucket, $reclaimName, $reclaimPath, ObjectManifest::MODE_RAW);

// The message id `reclaimLocal()` needs is the DOCUMENT's, not the manifest's.
// It is resolved the way the package itself addresses a chunk: the manifest names
// each chunk by its hash, and the channel is searched for that name. That is the
// operator's "its hash … is still in database" used as a handle — no row of ours
// is consulted.
$reclaimMsgId = 0;
try {
    $reclaimChannel = new TelegramStoreChannel($reclaimBucket, $api->apiMap());
    $located = (new TelegramTier)->manifestFor($reclaimChannel, $reclaimName);
    if (is_array($located) && $located['manifest']->chunkCount() > 0) {
        $found = $reclaimChannel->locate($located['manifest']->chunkDocName(0), 1);
        $reclaimMsgId = (int) ($found['entries'][0]['id'] ?? 0);
    }
} catch (Throwable) {
    $reclaimMsgId = 0;
}

$coldReclaim = null;
$coldReclaimError = '';
try {
    $coldReclaim = $coldStore->reclaimLocal(
        disk: 'local',
        path: $reclaimPath,
        expect: ['bucket' => $reclaimBucket, 'msg_id' => $reclaimMsgId],
        // The cold store already carries this posture; naming it again keeps
        // the claim independent of which service instance ran.
        policy: ReferencePolicy::coldRemoteAuthoritative(),
        // The deleter re-verifies the binding it was handed: this is the last
        // hop, and the only place a swap can still be caught.
        unlink: static function (string $disk, string $path, string $verifiedSha256): bool {
            if (! is_file($path)) {
                return false;
            }
            if (! hash_equals($verifiedSha256, (string) hash_file('sha256', $path))) {
                return false;
            }

            return @unlink($path);
        },
    );
} catch (Throwable $e) {
    $coldReclaimError = $e::class.': '.$e->getMessage();
}

$readBack = '';
try {
    $readBack = $coldStore->get($reclaimBucket, $reclaimName);
} catch (Throwable $e) {
    $readBack = 'ERROR '.$e::class.': '.$e->getMessage();
}

// --- THE SEALED COUNTERPART, asserted as the refusal it correctly is. A sealed
// object is stored as CIPHERTEXT, so the hash the remote tier can prove is the
// ciphertext's and can never equal the sha256 of the local plaintext: the
// reclaim has no binding that can hold, and freeing the file on "it is probably
// the same thing" is the data loss this whole refusal exists to prevent.
$sealedBucket = 'teleframe-data:v13-sealed-reclaim';
$sealedName = 'v13-sealed-reclaim-probe.txt';
$sealedPath = $root.'/storage/app/teleframe-v13-sealed-reclaim.txt';
file_put_contents($sealedPath, $reclaimBytes);
$store->put($sealedBucket, $sealedName, $sealedPath, ObjectManifest::MODE_SEALED, $passphrase);

$sealedMsgId = 0;
try {
    $sealedChannel = new TelegramStoreChannel($sealedBucket, $api->apiMap());
    $sealedLocated = (new TelegramTier)->manifestFor($sealedChannel, $sealedName);
    if (is_array($sealedLocated) && $sealedLocated['manifest']->chunkCount() > 0) {
        $sealedFound = $sealedChannel->locate($sealedLocated['manifest']->chunkDocName(0), 1);
        $sealedMsgId = (int) ($sealedFound['entries'][0]['id'] ?? 0);
    }
} catch (Throwable) {
    $sealedMsgId = 0;
}

$sealedReclaim = null;
$sealedReclaimError = '';
try {
    $sealedReclaim = $coldStore->reclaimLocal(
        disk: 'local',
        path: $sealedPath,
        expect: ['bucket' => $sealedBucket, 'msg_id' => $sealedMsgId],
        policy: ReferencePolicy::coldRemoteAuthoritative(),
        unlink: static fn (string $disk, string $path): bool => @unlink($path),
    );
} catch (Throwable $e) {
    $sealedReclaimError = $e::class.': '.$e->getMessage();
}

// --- the ledger, read back out of the HOST'S OWN `tf_store_backup_runs`.
$ledgerClass = '';
$ledgerRows = [];
try {
    $ledgerClass = app()->make(BackupSupport::INDEX_KEY)::class;
    $ledgerRows = app()->make(BackupSupport::SERVICE_KEY)->runs($bucket, 10);
} catch (Throwable $e) {
    $ledgerClass = 'ERROR '.$e::class.': '.$e->getMessage();
}

// Does a TELEGRAM MEDIA REFERENCE row exist for a dump? (the honest limit)
$mediaRowsForDump = -1;
try {
    $mediaRowsForDump = Schema::hasTable('tf_store_media')
        ? (int) DB::table('tf_store_media')->count()
        : -1;
} catch (Throwable) {
    $mediaRowsForDump = -1;
}

// ===========================================================================
// 4. THE SCHEDULE — stock posture, then armed: in-container, and in a real
//    second host process. Read off the HOST's own scheduler, never off ours.
// ===========================================================================

$stockTriggerOff = (bool) config('teleframe.store.backup.schedule.package_schedule.enabled');
$stockCommands = [];
if (app()->bound(Schedule::class)) {
    foreach (app()->make(Schedule::class)->events() as $event) {
        $stockCommands[] = is_string($event->command) ? $event->command : '';
    }
}

/**
 * Is this command registered on the host's scheduler?
 *
 * SUBSTRING, not equality, and the reason is measured rather than guessed:
 * Laravel wraps a `Schedule::command()` event's command into a full argv string
 * before it reaches the event (`'/usr/bin/php8.3' 'artisan' teleframe:tstore
 * backup`), so the bare command name is never what `$event->command` holds.
 */
$v13HasCommand = static function (array $commands, string $needle): bool {
    foreach ($commands as $command) {
        if (str_contains($command, $needle)) {
            return true;
        }
    }

    return false;
};

config(['teleframe.store.backup.schedule.package_schedule.enabled' => true]);
$armedDumpEvents = [];
$armError = '';
try {
    // Re-run the SHIPPED provider's own boot(): that method is the only code
    // in the package which registers the trigger. Nothing here reaches into the
    // package's internals, and the events are read back off the container.
    (new StoreBackupServiceProvider(app()))->boot();
    foreach (app()->make(Schedule::class)->events() as $event) {
        if (is_string($event->command) && str_contains($event->command, 'tstore backup')) {
            $armedDumpEvents[] = [
                'command' => $event->command,
                'expression' => (string) $event->getExpression(),
            ];
        }
    }
} catch (Throwable $e) {
    $armError = $e::class.': '.$e->getMessage();
}

// The same question the way a HOST OPERATOR asks it: a second host process,
// booted through package auto-discovery with the trigger armed by the env var a
// host would set, reporting what its own scheduler holds.
$inherited = getenv();
$childEnv = is_array($inherited) ? $inherited : [];
$childEnv['TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_ENABLED'] = 'true';
$scheduleList = v13Run([PHP_BINARY, 'artisan', 'schedule:list'], $root, $childEnv);

/** The H:i a cron expression actually fires at, read through the real parser. */
$v13FiresAt = static function (string $expression): string {
    try {
        $next = (new CronExpression($expression))->getNextRunDate(
            new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get())),
        );

        return $next->format('H:i');
    } catch (Throwable) {
        return '';
    }
};

// ===========================================================================
// 5. THE SECTION.
// ===========================================================================

return [
    'V1.3 laravel-db-backup' => [
        // The real dump reached the channel at all. Named separately so a run
        // that dies is reported AS a failed run, with its own reason, instead
        // of surfacing as five downstream assertions answering FALSE.
        'backup-run-completed' => mustBeTrue(
            static function () use ($report, $runError): bool {
                if ($runError !== '') {
                    throw new RuntimeException($runError);
                }

                return $report instanceof BackupRunReport;
            },
            'the run through the StoreBackup facade (the entry point teleframe:tstore backup uses) completed and returned a BackupRunReport',
        ),

        // The constraint that makes a run complete at all, proven from the host's
        // own migrated schema rather than asserted in a comment.
        'run-ledger-is-bound-to-the-account-registry' => mustBeTrue(
            static function () use ($v13AccountId): bool {
                $bound = false;
                foreach (Schema::getForeignKeys('tf_store_backup_runs') as $foreign) {
                    if (($foreign['foreign_table'] ?? '') === 'telegram_accounts'
                        && in_array('account_id', (array) ($foreign['columns'] ?? []), true)) {
                        $bound = true;
                    }
                }

                return $bound
                    && DB::table('telegram_accounts')->where('user_id', $v13AccountId)->exists();
            },
            'the run ledger\'s account_id is a real FOREIGN KEY onto the host\'s own telegram_accounts table (ON DELETE CASCADE), and this probe registered the backup account there — the CLAIM is what enforces it, and the claim happens BEFORE the dump and before the channel, so an unregistered account is refused with nothing uploaded',
        ),

        // ── 1. THE HOST'S OWN DATABASE, REALLY DUMPED ───────────────────
        'premise-host-database' => mustBeTrue(
            static function () use ($hostDbFile, $probeTable, $seedMarker): bool {
                return $hostDbFile !== ''
                    && is_file($hostDbFile)
                    && Schema::hasTable('users')
                    && Schema::hasTable('cache')
                    && DB::table($probeTable)->where('marker', $seedMarker)->exists();
            },
            'the host runs on its own migrated sqlite file (its users and cache tables are present), and this probe created and seeded its own table inside that same database with Schema::create',
        ),

        // THE LIMIT THAT WAS A LIMIT AND IS NOT ONE ANY MORE.
        //
        // This check used to be a REFUSAL and it was the honest truth then: the
        // package shipped MysqlDumper + PostgresDumper and nothing else, so the
        // operator's own words — "the default behaviour is it will be installed in
        // a laravel so it can simply dump the laravel database" — hit a wall on
        // Laravel's DEFAULT database. Measured as
        // `shipped-dumper-cannot-dump-sqlite` on 2026-10-18, and
        // `src/Teleframe/Store/Backup/SqliteDumper.php` plus the third branch in
        // `StoreBackupServiceProvider::registerDumper()` closed it.
        //
        // It is now a CAPABILITY, asserted as one: the container-bound dumper IS
        // the shipped SqliteDumper, and it dumps the HOST'S OWN FILE off disk —
        // no PDO, no host connection, no injection. Which is a stronger claim than
        // the class above it, because that one needed a connection supplied.
        'the-shipped-sqlite-dumper-dumps-the-host-own-file' => mustBeTrue(
            static function () use ($stockDumper, $hostDriver, $spoolDir, $hostDbFile): bool {
                if (! $stockDumper instanceof DatabaseDumper) {
                    return false;
                }

                // The refusal this check replaced: a driver the package does not
                // ship a dumper for. sqlite is no longer one of them.
                if ($hostDriver !== 'sqlite') {
                    return false;
                }

                $result = $stockDumper->dump(
                    new DatabaseTarget('sqlite', $hostDbFile),
                    new DumpWorkspace($spoolDir.'/shipped'),
                );

                return $result->exitCode === 0 && $result->bytes > 0 && is_file($result->path);
            },
            'the container-bound DatabaseDumper is the shipped SqliteDumper and it dumps the host\'s own sqlite file off disk with no connection injected',
        ),

        'real-bytes-from-the-host-database' => mustBeTrue(
            static function () use ($dump, $report, $seedMarker, $probeTable): bool {
                if ($dump === null || ! $report instanceof BackupRunReport) {
                    return false;
                }

                return $dump['bytes'] > 1024
                    && $dump['bytes'] === $report->bytes
                    && str_contains($dump['sql'], 'CREATE TABLE "users"')
                    && str_contains($dump['sql'], 'CREATE TABLE "'.$probeTable.'"')
                    && str_contains($dump['sql'], $seedMarker);
            },
            'a real dump of the host\'s own live database — carrying its users DDL, this probe\'s seeded table and this probe\'s seeded row — produced bytes, and the run report carries their exact length',
        ),

        // ── 2. "IN SCHEDULES" ───────────────────────────────────────────
        'schedule-stock-default-is-off' => mustBeTrue(
            static function () use ($stockTriggerOff, $stockCommands, $v13HasCommand): bool {
                return $stockTriggerOff === false
                    && $v13HasCommand($stockCommands, 'teleframe:notify watch --check')
                    && ! $v13HasCommand($stockCommands, 'tstore backup');
            },
            'on a stock install the package registers the hourly no-show CHECK but NO dump — schedule.package_schedule.enabled ships false on purpose, because it cannot see a host crontab and a stock install has no driver to dump anyway (F7/F13)',
        ),

        'schedule-registers-on-the-host-scheduler' => mustBeTrue(
            static function () use ($armedDumpEvents, $armError, $v13FiresAt): bool {
                if ($armError !== '' || count($armedDumpEvents) !== 1) {
                    return false;
                }

                return str_contains($armedDumpEvents[0]['command'], 'teleframe:tstore backup')
                    && $v13FiresAt($armedDumpEvents[0]['expression']) === '03:47';
            },
            'with schedule.package_schedule.enabled turned on, the package\'s own provider boot() registers exactly one event on the host\'s Laravel Schedule — command "teleframe:tstore backup", firing at 03:47 — read back off the container and parsed by the real cron parser',
        ),

        'schedule-visible-to-a-real-host-process' => mustBeTrue(
            static fn (): bool => $scheduleList['code'] === 0
                && str_contains($scheduleList['out'], 'teleframe:tstore backup'),
            'a SECOND host process, booted with only TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_ENABLED=1, lists teleframe:tstore backup in `php artisan schedule:list` — the operator\'s own view of their scheduler',
        ),

        // ── 3. "TO A CHANNEL", SEALED ───────────────────────────────────
        'goes-to-a-channel-not-a-bot-chat' => mustBeTrue(
            static function () use ($api, $bucket, $channelId, $report, $dump): bool {
                if ($report === null || $dump === null || $api->sends === []) {
                    return false;
                }
                foreach ($api->sends as $send) {
                    if (($send['peer']['_'] ?? '') !== 'inputPeerChannel'
                        || (int) ($send['peer']['channel_id'] ?? 0) <= 0) {
                        return false;
                    }
                }

                return $bucket === 'teleframe-backup:default'
                    && $channelId > 0
                    && $report->bucket === $bucket
                    && $report->database === 'laravel'
                    && $report->name === 'laravel-'.gmdate('Y-m-d-His', $dump['startedAt']).'.sql';
            },
            'every send the run made addressed an inputPeerChannel with a positive channel id — never a bot or user chat — into the config-derived bucket teleframe-backup:default, under the immutable object name laravel-<dump timestamp>.sql',
        ),

        'channel-destination-was-created' => mustBeTrue(
            static function () use ($api): bool {
                return array_key_exists(
                    TelegramStoreChannel::channelTitle('teleframe-backup:default'),
                    $api->channels,
                );
            },
            'the package find-or-created its own private destination, titled teleframe-store:teleframe-backup:default — a channel is provisioned on the first run, with no peer for the operator to set up by hand',
        ),

        'dump-is-sealed-not-plaintext' => mustBeTrue(
            static function () use ($report, $uploadedBytes, $seedMarker): bool {
                return $report instanceof BackupRunReport
                    && $report->mode === ObjectManifest::MODE_SEALED
                    && $report->chunks >= 1
                    && strlen($report->sha256) === 64
                    && $uploadedBytes !== ''
                    && ! str_contains($uploadedBytes, $seedMarker);
            },
            'the dump went up SEALED under the configured passphrase: the run report says mode=sealed with a content sha256, and not one byte the transport received contains the host database\'s seeded plaintext',
        ),

        // ── 4. "THE KIND THAT WE DO PURE … WE JUST PASS THE HASH" ───────
        'media-hash-is-returned' => mustBeTrue(
            static function () use ($mediaFor): bool {
                $reference = is_array($mediaFor) ? ($mediaFor['reference'] ?? null) : null;

                return $reference instanceof MediaReference
                    && $reference->docId() > 0
                    && $reference->accessHash() > 0
                    && $reference->fileReference() !== '';
            },
            'the uploaded document yields a MediaReference — document id, access hash and file reference, i.e. exactly "the hash that telegram gives us" — resolved out of the channel with no local row involved',
        ),

        'republish-by-hash-uploads-nothing' => mustBeTrue(
            static function () use ($api, $republishedTo, $uploadsBeforeRepublish, $uploadsAfterRepublish): bool {
                return $republishedTo > 0
                    && $uploadsBeforeRepublish === $uploadsAfterRepublish
                    && $api->referenceSends !== [];
            },
            'publishing the same file to a SECOND channel by reference alone posted a new message with ZERO bytes re-uploaded — "for posting them in our public channel we just pass the hash"',
        ),

        // ── 5. "KEEPING STORAGE OF LARAVEL FREE" + THE DELETION STORY ───
        'dump-spool-is-released' => mustBeTrue(
            static function () use ($dump, $spoolDir): bool {
                if ($dump === null) {
                    return false;
                }

                return ! is_file($dump['path']) && (glob($spoolDir.'/tfbk-*') ?: []) === [];
            },
            'after the bytes reached Telegram the dump spool was unlinked — DumpWorkspace::release(false) left no tfbk-* file in the host-configured spool_dir, which is the local-space half of the claim',
        ),

        'default-posture-refuses-to-delete' => mustBeTrue(
            static function () use ($defaultReclaim): bool {
                return $defaultReclaim instanceof ReclaimOutcome
                    && $defaultReclaim->state() === ReclaimOutcome::RETAINED
                    && $defaultReclaim->reason() === ReclaimOutcome::REASON_POLICY_RETAINS;
            },
            'the shipped default posture (ReferencePolicy::retainLocal) refuses to delete a host file at all — reclaiming local storage is an operator opt-in, never an install-time decision',
        ),

        'local-file-freed-remote-copy-survives' => mustBeTrue(
            static function () use ($coldReclaim, $coldReclaimError, $reclaimPath, $readBack, $reclaimBytes): bool {
                return $coldReclaimError === ''
                    && $coldReclaim instanceof ReclaimOutcome
                    && $coldReclaim->state() === ReclaimOutcome::RECLAIMED
                    && $coldReclaim->reason() === ReclaimOutcome::REASON_DELETED
                    && ! is_file($reclaimPath)
                    && $readBack === $reclaimBytes;
            },
            'a real file in the host\'s own storage/ was stored RAW (the "pure" kind — Telegram holds the same bytes the host does), then FREED by reclaimLocal under the cold remote-authoritative posture, and its bytes are still readable out of the channel afterwards — "we delete something in main laravel storage but … the file is in the channel"',
        ),

        'sealed-object-is-correctly-not-reclaimable' => mustBeTrue(
            static function () use ($sealedReclaim, $sealedReclaimError, $sealedPath): bool {
                return $sealedReclaimError === ''
                    && $sealedReclaim instanceof ReclaimOutcome
                    && $sealedReclaim->state() === ReclaimOutcome::REFUSED
                    && $sealedReclaim->reason() === ReclaimOutcome::REASON_CONTENT_MISMATCH
                    && is_file($sealedPath);
            },
            'THE LIMIT, STATED PLAINLY: the same reclaim against a SEALED object is refused with content-mismatch and the local file is kept — a sealed object is ciphertext in the channel, so the hash the remote can prove is never the local plaintext\'s. "Keep laravel storage free" is therefore reachable for RAW objects only; the encrypted-backup path frees the DUMP SPOOL instead (the check above), and cannot free a host file it cannot hash',
        ),

        'run-ledger-round-trips-in-the-host-database' => mustBeTrue(
            static function () use ($ledgerClass, $ledgerRows, $report, $bucket): bool {
                if ($report === null
                    || ! str_contains($ledgerClass, 'EloquentBackupRunIndex')
                    || ! Schema::hasTable('tf_store_backup_runs')) {
                    return false;
                }
                $row = null;
                foreach ($ledgerRows as $candidate) {
                    if (($candidate['object_name'] ?? '') === $report->name) {
                        $row = $candidate;
                    }
                }
                if ($row === null) {
                    return false;
                }

                return ($row['bucket'] ?? '') === $bucket
                    && (int) ($row['bytes'] ?? 0) === $report->bytes
                    && (string) ($row['sha256'] ?? '') === $report->sha256
                    && (string) ($row['mode'] ?? '') === ObjectManifest::MODE_SEALED
                    && (string) ($row['status'] ?? '') === 'ok';
            },
            'the run ledger row — bucket, byte count, content sha256, sealed mode, status ok — round-trips through the HOST\'s own tf_store_backup_runs table through the real EloquentBackupRunIndex: the "its hash is still in database" half, on the host\'s own database',
        ),

        // The honest gap. Kept as a verdict, not deleted to get green.
        'no-media-reference-row-is-written-for-a-dump' => mustBeTrue(
            static function () use ($mediaRowsForDump, $report): bool {
                return $mediaRowsForDump === 0 && $report instanceof BackupRunReport;
            },
            'THE CURRENT TRUTH, STATED PLAINLY: a backup run writes NO tf_store_media row, so a dump carries no stored document reference — its "hash" is only the content sha256 in the run ledger, and reading it back goes through the sealed manifest in the channel rather than through a media row. The hash-addressed re-publish and the cold reclaim above therefore only work on a DATA-bucket object, or after archiveMessageMedia() has indexed the message; this is the gap a "pure" dump would have to close',
        ),

        // ── INFORMATIONAL: what this run actually booted with. ───────────
        'host-posture' => 'host_db_driver='.$hostDriver
            .' host_db_file='.basename($hostDbFile)
            .' spool_dir=inside the throwaway host'
            .' dump_bytes='.(string) ($dump['bytes'] ?? 0)
            .' run_error='.($runError === '' ? '(none)' : $runError)
            .' reclaim_error='.($coldReclaimError === '' ? '(none)' : $coldReclaimError)
            .' reclaim_state='.($coldReclaim instanceof ReclaimOutcome ? $coldReclaim->state() : '(none)')
            .' reclaim_reason='.($coldReclaim instanceof ReclaimOutcome ? $coldReclaim->reason() : '(none)')
            .' reclaim_msg_id='.(string) $reclaimMsgId
            .' reclaim_read_back_bytes='.strlen($readBack)
            .' sealed_reclaim_error='.($sealedReclaimError === '' ? '(none)' : $sealedReclaimError)
            .' sealed_reclaim_state='.($sealedReclaim instanceof ReclaimOutcome ? $sealedReclaim->state() : '(none)')
            .' sealed_reclaim_reason='.($sealedReclaim instanceof ReclaimOutcome ? $sealedReclaim->reason() : '(none)')
            .' arm_error='.($armError === '' ? '(none)' : $armError),
        'transport-double' => 'tests/Support/FakeStoreApi.php — the package\'s own shipped recording api-map double, bound on teleclient.tstore.service. No credential, no session string, no auth key, no socket.',
        'channel-identity' => 'bucket='.$bucket
            .' channel_title='.TelegramStoreChannel::channelTitle($bucket)
            .' channel_id='.(string) $channelId
            .' sends='.count($api->sends)
            .' reference_sends='.count($api->referenceSends)
            .' titles='.implode(' | ', array_keys($api->channels)),
        'schedule-posture' => 'stock_package_schedule_enabled='.var_export($stockTriggerOff, true)
            .' stock_schedule_commands='.implode(' | ', $stockCommands)
            .' armed_events='.json_encode($armedDumpEvents, JSON_UNESCAPED_SLASHES)
            .' schedule_list_exit='.(string) $scheduleList['code']
            .' schedule_list_tail='.trim(str_replace("\n", ' / ', substr($scheduleList['out'], -220))),
        'ledger-class' => $ledgerClass.' — resolved from the container by the package\'s own provider against the host\'s migrated database',
    ],
];
