<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunIndexContract;
use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\TemporalBackupScheduler;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\StoreMaintenanceScheduleSpec;
use MeRezaRezaei\Teleframe\Store\StoreService;
use RuntimeException;

/**
 * artisan teleframe:tstore (verbatim S1–S11, plan W7): the CLI shell over
 * StoreService — buckets (list registry), sync (walk dialogs + upsert
 * buckets), put/get, ls, index (S3 media walk of a data channel), verify,
 * prune (hot-cache sweep), work (drain the async upload queue), schedules
 * (probe/install Temporal schedules).
 *
 * Every dependency comes from the container through the six public bind
 * keys below (mirrors BackfillCommand/BackupCommand seams). The provider
 * default resolves from teleframe.tstore config; hosts rebind any key.
 *
 * `backup` and `schedule` (design 2026-09-24 §7) are the store-backup
 * face: `backup` runs one DB dump → sealed put → run-ledger row →
 * heartbeat (seams under BackupSupport from StoreBackupServiceProvider);
 * `schedule` describes the BackupScheduleSpec install payload and the
 * watchdog registration status for the 'backup' topic.
 */
final class TstoreCommand extends Command
{
    /** Container seam: callable(string $bucket): TelegramStoreChannel */
    public const CHANNEL_FACTORY_KEY = 'teleclient.tstore.channel-factory';

    /** Container seam: StoreIndexContract */
    public const INDEX_KEY = 'teleclient.tstore.index';

    /** Container seam: HotCacheContract */
    public const HOT_CACHE_KEY = 'teleclient.tstore.hot-cache';

    /** Container seam: UploadQueueContract */
    public const QUEUE_KEY = 'teleclient.tstore.queue';

    /** Container seam: SchedulePublisher */
    public const SCHEDULES_KEY = 'teleclient.tstore.schedules';

    /** Container seam: StoreService (built from the four seams above) */
    public const SERVICE_KEY = 'teleclient.tstore.service';

    private const ACTIONS = ['buckets', 'sync', 'put', 'get', 'ls', 'index', 'verify', 'prune', 'work', 'schedules', 'backup', 'schedule', 'publish'];

    protected $signature = 'teleframe:tstore
        {action : buckets|sync|put|get|ls|index|verify|prune|work|schedules|backup|schedule|publish}
        {bucket? : Bucket name (put/get/ls/index/verify/publish)}
        {name? : Object name (put/get/verify) — for publish, the SOURCE message id}
        {source? : Source path for put — for publish, the destination inputPeer as JSON}
        {--mode=raw : raw|sealed}
        {--passphrase-file= : Read the object passphrase from a file (0600) — or env TELEGRAM_TSTORE_PASSPHRASE}
        {--ask-passphrase : Prompt for the object passphrase on a hidden TTY prompt}
        {--mime=application/octet-stream : MIME for put}
        {--queued : Dispatch through the async queue instead of inline}
        {--buckets= : Comma-separated buckets for sync}
        {--max=1 : Job count for work}
        {--out= : Write get output to a file instead of stdout}
        {--caption= : Caption for publish}
        {--peer= : Destination inputPeer as JSON — an alternative to the source argument}
        {--prune= : backup — keep the newest N runs in the bucket, delete the rest (0 = delete all)}
        {--force : confirm the irreversible parts; required by --prune=0, which deletes every run in the bucket}
        {--install : schedules — ARM the tstore maintenance schedules through the schedule seam (opt-in; the default driver is null, which reports "skipped")}
        {--every-prune=3600 : schedules — hot-cache prune cadence in seconds (with --install)}
        {--every-sweep=86400 : schedules — registry sweep cadence in seconds (with --install)}';

    protected $description = 'Telegram-backed encrypted archive: buckets, sync, put, get, ls, index, verify, prune, work, schedules (describe, or arm with --install); store backup: dump the host DB to a channel (backup [--prune=N]) and inspect the schedule/watchdog (schedule); publish: repost an indexed file to another peer by its Telegram hash (publish <bucket> <msgId> <inputPeer-json> [--caption=…])';

    protected $aliases = [];

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        if (! in_array($action, self::ACTIONS, true)) {
            $this->error("unknown action \"{$action}\" — expected ".implode('|', self::ACTIONS).'.');

            return self::FAILURE;
        }

        try {
            return match ($action) {
                'buckets' => $this->buckets(),
                'sync' => $this->sync(),
                'put' => $this->put(),
                'get' => $this->get(),
                'ls' => $this->ls(),
                'index' => $this->index(),
                'verify' => $this->verify(),
                'prune' => $this->prune(),
                'work' => $this->work(),
                'schedules' => $this->schedules(),
                'backup' => $this->backup(),
                'schedule' => $this->schedule(),
                'publish' => $this->publish(),
            };
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function buckets(): int
    {
        $rows = $this->storeService()->buckets();
        if ($rows === []) {
            $this->info('No buckets registered yet (run teleframe:tstore sync).');

            return self::SUCCESS;
        }

        $this->table(['bucket', 'kind', 'channel_id'], array_map(
            static fn (array $row): array => [$row['bucket'], $row['kind'], $row['channel_id']],
            $rows,
        ));

        return self::SUCCESS;
    }

    private function sync(): int
    {
        $list = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('buckets')))));
        $report = $this->storeService()->syncRegistry($list);
        $this->info("Registry sync: walked {$report['walked']} dialogs, upserted {$report['upserted']} buckets.");

        return self::SUCCESS;
    }

    private function put(): int
    {
        $bucket = (string) $this->argument('bucket');
        $name = (string) $this->argument('name');
        $source = (string) $this->argument('source');
        if ($bucket === '' || $name === '' || $source === '') {
            $this->error('put needs bucket, name and a source path.');

            return self::FAILURE;
        }

        $result = $this->storeService()->put(
            $bucket,
            $name,
            $source,
            (string) $this->option('mode'),
            $this->passphrase(),
            (string) $this->option('mime'),
            (bool) $this->option('queued'),
        );

        if ($result['queued']) {
            $this->info("Queued upload job {$result['job']} for {$bucket}/{$name}.");

            return self::SUCCESS;
        }

        $this->info("Uploaded {$bucket}/{$name} (".$result['object']['size'].' bytes, '
            .$result['manifest']['mode'].', '.count($result['manifest']['chunks']).' chunks).');

        return self::SUCCESS;
    }

    /**
     * Repost an already-stored file to another peer by its Telegram hash.
     *
     * The CLI face of `StoreService::publishByReference()`: Telegram's own
     * `file_reference` goes straight to the wire as an inputMediaDocument —
     * no download, no re-upload, no temp file. That capability was correct and
     * completely unreachable without writing PHP (the `TStore` entry was an
     * `@method` docblock), so the owner sentence "for posting them in our
     * public channel we just pass the hash" was not usable from a terminal.
     *
     * A row that is not indexed fails LOUDLY, and that is preserved here: a
     * silent re-upload is exactly the cost this feature exists to remove, so
     * the CLI must not paper over it into a success. The `handle()` catch turns
     * that RuntimeException into a non-zero exit and prints the reason.
     *
     * The destination peer is host input, so it is VALIDATED before any wire
     * call rather than trusted: malformed JSON, a non-array, or anything that
     * is not an `inputPeer*` is refused with the reason.
     */
    private function publish(): int
    {
        $bucket = trim((string) $this->argument('bucket'));
        $msgId = trim((string) $this->argument('name'));
        $peerJson = trim((string) $this->argument('source'));
        if ($peerJson === '') {
            $peerJson = trim((string) $this->option('peer'));
        }

        if ($bucket === '') {
            $this->error('publish needs a bucket.');

            return self::FAILURE;
        }

        if ($msgId === '' || ! ctype_digit($msgId) || (int) $msgId <= 0) {
            $this->error('publish needs a positive integer message id (the SOURCE message whose hash Telegram gave you).');

            return self::FAILURE;
        }

        if ($peerJson === '') {
            $this->error('publish needs a destination peer as JSON (e.g. {"_":"inputPeerChannel","channel_id":900,"access_hash":4242}) — pass it as the third argument or via --peer.');

            return self::FAILURE;
        }

        $peer = $this->decodePeer($peerJson);
        if ($peer === null) {
            return self::FAILURE;
        }

        $posted = $this->storeService()->publishByReference(
            $bucket,
            (int) $msgId,
            $peer,
            (string) $this->option('caption'),
        );

        $this->info("Published {$bucket} message {$msgId} to the peer as message {$posted} (by reference — no download, no re-upload).");

        return self::SUCCESS;
    }

    /**
     * A destination inputPeer, or null after printing why the input is not one.
     *
     * Zero regex by repo spec: the `_` discriminator is checked with a string
     * prefix test, which is exactly the shape the wire itself uses.
     *
     * @return array<string, mixed>|null
     */
    private function decodePeer(string $json): ?array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            $this->error('publish could not read the destination peer: it must be a JSON object describing an inputPeer.');

            return null;
        }

        $type = $decoded['_'] ?? null;
        if (! is_string($type) || ! str_starts_with($type, 'inputPeer')) {
            $this->error('publish refused the destination peer: "_" must be an inputPeer* type, got '
                .(is_string($type) ? $type : 'nothing').'.');

            return null;
        }

        return $decoded;
    }

    private function get(): int
    {
        $bucket = (string) $this->argument('bucket');
        $name = (string) $this->argument('name');
        if ($bucket === '' || $name === '') {
            $this->error('get needs bucket and name.');

            return self::FAILURE;
        }

        $bytes = $this->storeService()->get($bucket, $name, $this->passphrase());
        $out = (string) ($this->option('out') ?? '');
        if ($out !== '') {
            file_put_contents($out, $bytes);
            $this->info("Wrote {$bucket}/{$name} to {$out} (".strlen($bytes).' bytes).');

            return self::SUCCESS;
        }

        $this->line($bytes);

        return self::SUCCESS;
    }

    private function ls(): int
    {
        $bucket = (string) $this->argument('bucket');
        if ($bucket === '') {
            $this->error('ls needs a bucket.');

            return self::FAILURE;
        }

        $rows = $this->storeService()->objects($bucket);
        if ($rows === []) {
            $this->info("No objects in {$bucket}.");

            return self::SUCCESS;
        }

        $this->table(['name', 'mode', 'size', 'sha256'], array_map(
            static fn (array $row): array => [
                $row['name'],
                $row['mode'],
                $row['size'],
                substr((string) (($row['chunks'][0]['sha256'] ?? $row['metadata']['sha256'] ?? '')), 0, 16).'…',
            ],
            $rows,
        ));

        return self::SUCCESS;
    }

    private function index(): int
    {
        $bucket = (string) $this->argument('bucket');
        if ($bucket === '') {
            $this->error('index needs a data bucket.');

            return self::FAILURE;
        }

        $count = $this->storeService()->indexDataChannel($bucket);
        $this->info("Indexed {$count} media rows from {$bucket}.");

        return self::SUCCESS;
    }

    private function verify(): int
    {
        $bucket = (string) $this->argument('bucket');
        $name = (string) $this->argument('name');
        if ($bucket === '' || $name === '') {
            $this->error('verify needs bucket and name.');

            return self::FAILURE;
        }

        $report = $this->storeService()->verify($bucket, $name, $this->passphrase());
        if ($report['ok'] === true) {
            $this->info("{$bucket}/{$name} verifies OK (".$report['chunks'].' chunks).');

            return self::SUCCESS;
        }

        foreach ($report['errors'] as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    private function prune(): int
    {
        $evicted = $this->storeService()->prune();
        $this->info("Hot-cache sweep evicted {$evicted} chunk(s).");

        return self::SUCCESS;
    }

    private function work(): int
    {
        $queue = $this->getLaravel()->make(self::QUEUE_KEY);
        if (! $queue->isAsync()) {
            $this->info('Queue driver is sync — nothing to drain. Use put --queued with the amqp driver.');

            return self::SUCCESS;
        }

        $service = $this->storeService();
        $max = max(1, (int) $this->option('max'));
        $handled = $queue->consume(static fn (array $payload): mixed => $service->putFromJob($payload), $max);
        $this->info("Drained {$handled} upload job(s).");

        return self::SUCCESS;
    }

    /**
     * `teleframe:tstore schedules` — the tstore maintenance schedule seam.
     *
     * DEFAULT: describe() only, and UNCHANGED by this. What changed is that
     * there is now a way to ARM it. Until ab1961bc the seam could select a
     * driver and nothing on it ever called install(): the driver was
     * selectable, the publisher describable, and the recurring work a
     * description. A host that set TELEFRAME_TSTORE_SCHEDULES=temporal got
     * no job, and the only honest thing the text could say was "nothing is
     * scheduled" — which is why NullSchedulePublisher refused to name this
     * key.
     *
     * WHERE ARMING HAPPENS, and why exactly here:
     *
     *  - NOT AT BOOT. A provider that armed on every web request or every
     *    queue tick would dial Temporal from a request, and the ruling that
     *    a package must not start recurring jobs the moment it is installed
     *    is not this slice's to relitigate. The bindings stay deferred
     *    singletons; nothing resolves or dials in register().
     *  - NOT ON EVERY INVOCATION. Every other action of this command would
     *    then upsert two schedules, so `tstore ls` would talk to Temporal.
     *    That is a new failure mode, not a fix.
     *  - SO: one explicit operator action, `--install`, on the action that
     *    already owns the seam — the same shape the two sibling seams
     *    already use (`teleframe:app calls --install`,
     *    `teleframe:notify schedules --install`). One artisan invocation,
     *    one arm.
     *
     * RE-ARMING IS SAFE, NOT ACCIDENTALLY SAFE. installSpec() is
     * deterministic (same ids every time) and the client upserts by id, so
     * running `--install` on every deploy refreshes the two schedules
     * instead of stacking them. The provider's default driver is still
     * 'null': with it, install() honestly reports 'skipped (null schedule
     * driver)' per entry and the command says so — arming with the null
     * driver is a visible no-op, never a false success.
     */
    private function schedules(): int
    {
        $publisher = $this->getLaravel()->make(self::SCHEDULES_KEY);
        if (! $publisher instanceof SchedulePublisher) {
            throw new RuntimeException(self::SCHEDULES_KEY.' must bind a SchedulePublisher.');
        }

        if (! (bool) $this->option('install')) {
            $this->info($publisher->describe());
            $this->info((new StoreMaintenanceScheduleSpec)->describe());

            return self::SUCCESS;
        }

        $cadences = [
            '--every-prune' => (int) $this->option('every-prune'),
            '--every-sweep' => (int) $this->option('every-sweep'),
        ];
        foreach ($cadences as $option => $seconds) {
            if ($seconds <= 0) {
                $this->error("{$option} must be a positive number of seconds — a non-positive cadence is an unthrottled loop, not a schedule.");

                return self::FAILURE;
            }
        }

        $spec = new StoreMaintenanceScheduleSpec(
            $cadences['--every-prune'],
            $cadences['--every-sweep'],
        );

        $status = $publisher->install($spec->installSpec());

        $rows = [];
        foreach ($spec->installSpec() as $id => $payload) {
            $rows[] = [
                'schedule' => $id,
                'activity' => $payload['activity'],
                'interval_seconds' => $payload['interval_seconds'],
                'status' => (string) ($status[$id] ?? 'not reported by the publisher'),
            ];
        }
        $this->table(['schedule', 'activity', 'interval_seconds', 'status'], $rows);

        $this->info(count($rows).' schedule(s) handed to '.self::SCHEDULES_KEY
            .' — re-running --install refreshes the same ids, it never stacks them.');

        return self::SUCCESS;
    }

    /**
     * One store-backup run (design 2026-09-24 §4): DB dump → sealed put
     * into the configured teleframe-backup: bucket → run-ledger row →
     * heartbeat. `--queued` hands the upload to the async queue when the
     * host runs one (sync driver runs inline regardless).
     *
     * The target is the config-built default (teleframe.store.backup.
     * database.*) that StoreBackupServiceProvider binds under
     * DatabaseTarget::class — the core never fabricates one config-free
     * (B3 RULING: backup(null) throws), so the CLI resolves it here.
     */
    private function backup(): int
    {
        // The config-built default target (teleframe.store.backup.
        // database.*) that StoreBackupServiceProvider binds under
        // DatabaseTarget::class. Without the provider the container throws
        // (BindingResolutionException — a RuntimeException, loud through
        // handle()) — the core never fabricates a target config-free.
        $target = $this->getLaravel()->make(DatabaseTarget::class);

        $report = $this->backupService()->backup(
            $target,
            (bool) $this->option('queued'),
        );

        $rows = [];
        foreach ($report->toArray() as $key => $value) {
            $rows[] = [$key, is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR)];
        }

        $this->table(['key', 'value'], $rows);
        $this->info('Backup stored: '.$report->name.' → '.$report->bucket
            .' ('.$report->bytes.' bytes, '.$report->chunks.' chunks, '.$report->mode.($report->queued ? ', queued' : '').').');

        // Wave-2-D retention: backup --prune=N keeps the newest N runs in
        // the run bucket (the one just stored is the newest), deleting the
        // rest via the run ledger + StoreService::delete (design §6:
        // retention = listRuns + deleteRun + delete; "managing indexes").
        $prune = (string) $this->option('prune');
        if ($prune !== '') {
            if (! ctype_digit($prune)) {
                $this->error('--prune must be a non-negative integer (number of newest runs to keep).');

                return self::FAILURE;
            }

            // --prune=0 keeps nothing, which is the whole point of the flag and
            // also the most destructive thing this command can do: every chunk
            // document, every TFSTORE1 metadata message and every ledger row in
            // the bucket, irrecoverably. It was one bare flag away with no
            // confirmation and no --force, so a mistyped or over-eager prune
            // argument destroyed a tenant's entire backup history. The total
            // wipe now has to be asked for twice.
            if ((int) $prune === 0 && ! $this->option('force')) {
                $this->error(
                    '--prune=0 deletes EVERY backup run in '.$report->bucket
                    .' — every chunk, every metadata message, every ledger row, unrecoverably. '
                    .'Re-run with --force if that is what you meant.'
                );

                return self::FAILURE;
            }

            $pruned = $this->pruneRuns($report->bucket, (int) $prune);
            $this->info("Pruned {$pruned} run(s) — kept the newest {$prune} in ".$report->bucket.'.');
        }

        return self::SUCCESS;
    }

    /**
     * Delete every run row beyond the newest $keep in $bucket: ledger
     * forget (BackupRunIndexContract::deleteRun) + Telegram-side object
     * delete (StoreService::delete — chunk docs + TFSTORE1 metadata
     * message). Rows come from the service ledger newest-first, so
     * array_slice($rows, $keep) is exactly the delete set.
     *
     * $keep = 0 deletes everything (documented "--prune=0 = delete all").
     *
     * @return int the number of runs deleted
     */
    private function pruneRuns(string $bucket, int $keep): int
    {
        $rows = $this->backupService()->runs($bucket, PHP_INT_MAX);
        $pruned = 0;
        foreach (array_slice($rows, $keep) as $row) {
            $objectName = (string) ($row['object_name'] ?? '');
            if ($objectName === '') {
                continue;
            }

            if (! $this->mayPruneTarget($bucket)) {
                throw new BackupException(
                    'prune refused: the configured target has no post/admin rights — deleteMessages on a non-admin target is a runtime error (design §6).'
                );
            }

            $this->storeService()->delete($bucket, $objectName);
            $index = $this->getLaravel()->make(BackupSupport::INDEX_KEY);
            if ($index instanceof BackupRunIndexContract) {
                $index->deleteRun((int) ($row['account_id'] ?? 0), $objectName);
            }
            $pruned++;
        }

        return $pruned;
    }

    /**
     * Post-rights guard for prune (design §6, wave-2-D): deleting
     * Telegram-side messages requires post/admin rights on the target.
     * Consulted against the WAVE-2-A registry seam
     * (teleclient.store.backup.targets, anchored by BackupSupport::
     * TARGETS_KEY): a bucket configured as a target row resolves through
     * PeerTargetRegistry::byBucket(), and the post-rights ruling follows
     * the PeerMembership contract — channel targets are assumed OWNED
     * (created by the account → admin by construction) → true, and group
     * post-rights probe via getParticipants is best-effort with a
     * PERMISSIVE default true (PeerMembership.php: "such targets are the
     * caller's responsibility to verify (e.g. on prune)") — so the seam
     * cannot deny today; this guard exists as the check point where the
     * probe lands. No configured target row (or no registry) = the
     * owned-channel default (syncRegistry/createChannel posture) → true.
     *
     * Resolved via string-literal class probes so this command compiles
     * even while the sibling classes are mid-landing; both landed within
     * this cycle (cycle 11 wave 2).
     */
    private function mayPruneTarget(string $bucket): bool
    {
        $container = $this->getLaravel();
        $registry = $container->bound(BackupSupport::TARGETS_KEY)
            ? $container->make(BackupSupport::TARGETS_KEY)
            : null;
        if ($registry === null) {
            return true; // no configured target rows — owned-channel default
        }

        $registryClass = 'MeRezaRezaei\\Teleframe\\Store\\Backup\\PeerTargetRegistry';
        if (! $registry instanceof $registryClass) {
            return true; // sibling class not landed — owned-channel default
        }

        $target = $registry->byBucket($bucket);
        if ($target === null) {
            return true; // owned bucket, not a configured target row
        }

        // PeerMembership post-rights ruling (channel→owned→true; group →
        // permissive true) — the probe point for a future scope seam.
        return true;
    }

    /**
     * Describe the backup schedule seam (design §5, wave-2-D): the
     * BackupScheduleSpec install payload + the TemporalBackupScheduler
     * describe() (when the seam is bound — the host's Temporal install
     * face) + the watchdog registration status for 'backup'.
     *
     * `--install` ARMS it. This action was describe-only for the whole life
     * of the seam, and that made the worked example a worked example of
     * nothing: `TemporalBackupScheduler::install()` existed, the provider
     * bound the scheduler (StoreBackupServiceProvider:535), and no
     * production code called it — `grep -rn 'scheduler->install' src/`
     * returned nothing. So the recurring database dump, the literal
     * "make a backup from it in schedules", could not fire itself on ANY
     * seam in this package.
     *
     * The arming point is here rather than in a provider for the same
     * reasons the `schedules` action gives: not at boot (a provider that
     * armed on every request would dial Temporal from a web request), and
     * not on every invocation (every other action would then upsert). One
     * explicit operator action; BackupScheduleSpec::installSpec() is
     * deterministic and the client upserts by schedule id, so re-running
     * --install on every deploy refreshes the two schedules
     * (backup-dump, backup-watchdog-check) instead of stacking them.
     */
    private function schedule(): int
    {
        $spec = $this->scheduleSpec();
        $this->info($spec->describe());

        // Wave-2-D: when the scheduler seam is bound (StoreBackupService
        // Provider always binds it — NullSchedulePublisher default), print
        // the publisher posture; hosts rebinding a TemporalSchedule
        // Publisher see its live state here. Unbound (no provider) falls
        // back to the spec describe() printed above.
        $container = $this->getLaravel();
        $scheduler = null;
        if ($container->bound(BackupSupport::SCHEDULER_KEY)) {
            $bound = $container->make(BackupSupport::SCHEDULER_KEY);
            if ($bound instanceof TemporalBackupScheduler) {
                $scheduler = $bound;
                $this->info('Scheduler: '.$scheduler->describe());
            }
        }

        if ((bool) $this->option('install')) {
            $this->armBackupSchedules($scheduler);
        }

        $watchdog = $this->getLaravel()->make(BackupSupport::WATCHDOG_KEY);
        if (! $watchdog instanceof NoShowWatchdog) {
            $this->info('Watchdog: no seam bound ('.BackupSupport::WATCHDOG_KEY.') — the "backup" heartbeat is not registered.');

            return self::SUCCESS;
        }

        if ($watchdog->isRegistered('backup')) {
            $expectEvery = $watchdog->registrations()['backup']['expected_every'];
            $lastSeen = $watchdog->lastSeen('backup');
            $this->info('Watchdog: "backup" registered (expectEverySeconds '.$expectEvery.', lastSeen '
                .($lastSeen === null ? 'never (grace period)' : gmdate('c', $lastSeen)).').');
        } else {
            $this->info('Watchdog: "backup" is NOT registered (set teleframe.store.backup.schedule.enabled).');
        }

        return self::SUCCESS;
    }

    /**
     * Hand the BackupScheduleSpec payload to the backup publisher seam and
     * print the per-schedule status it reports.
     *
     * A MISSING SEAM is a loud failure, not a skip: the operator asked to
     * arm a recurring database dump, and printing SUCCESS while nothing was
     * handed to a publisher is precisely the false-success this whole seam
     * exists to prevent. The RuntimeException reaches handle()'s catch, so
     * the command exits FAILURE with the reason printed.
     */
    private function armBackupSchedules(?TemporalBackupScheduler $scheduler): void
    {
        if ($scheduler === null) {
            throw new RuntimeException(
                '--install needs the '.BackupSupport::SCHEDULER_KEY.' seam, and nothing is bound to it. '
                .'Register '.StoreBackupServiceProvider::class.' (it binds the scheduler over the '
                .BackupSupport::SCHEDULES_KEY.' publisher), or run without --install to inspect the seam.'
            );
        }

        $status = $scheduler->install();

        $rows = [];
        foreach ($this->scheduleSpec()->installSpec() as $id => $payload) {
            $rows[] = [
                'schedule' => $id,
                'activity' => $payload['activity'],
                'interval_seconds' => $payload['interval_seconds'],
                'status' => (string) ($status[$id] ?? 'not reported by the publisher'),
            ];
        }
        $this->table(['schedule', 'activity', 'interval_seconds', 'status'], $rows);

        $this->info(count($rows).' backup schedule(s) handed to '.BackupSupport::SCHEDULES_KEY
            .' — re-running --install refreshes the same ids, it never stacks them.');
    }

    private function backupService(): DatabaseBackupService
    {
        $service = $this->getLaravel()->make(BackupSupport::SERVICE_KEY);
        if (! $service instanceof DatabaseBackupService) {
            throw new RuntimeException(BackupSupport::SERVICE_KEY.' must bind a '.DatabaseBackupService::class.'.');
        }

        return $service;
    }

    /**
     * The BackupScheduleSpec for the current interval — built directly from
     * teleframe.store.backup.schedule.interval_seconds (the core service
     * has no schedule() accessor: BackupScheduleSpec is a plain-PHP
     * config→install-payload builder, design §5).
     */
    private function scheduleSpec(): BackupScheduleSpec
    {
        $interval = (int) ($this->getLaravel()->make('config')->get('teleframe.store.backup.schedule.interval_seconds') ?? 86400);

        return new BackupScheduleSpec($interval);
    }

    private function storeService(): StoreService
    {
        $service = $this->getLaravel()->make(self::SERVICE_KEY);
        if (! $service instanceof StoreService) {
            throw new RuntimeException(self::SERVICE_KEY.' must bind a StoreService.');
        }

        return $service;
    }

    /**
     * The object passphrase, WITHOUT a --passphrase= option.
     *
     * argv is world-readable in /proc and lands in shell history, CI logs and
     * process listings, so a secret passed there is a leaked secret. The
     * sibling BackupCommand had the same defect and now uses
     * env -> --passphrase-file -> hidden prompt; this is the same ladder.
     * The prompt only fires on a real TTY, so cron never blocks on it.
     */
    private function passphrase(): ?string
    {
        $fromEnv = (string) env('TELEGRAM_TSTORE_PASSPHRASE', '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $file = (string) $this->option('passphrase-file');
        if ($file !== '') {
            if (! is_readable($file)) {
                throw new RuntimeException('passphrase file is not readable: '.$file);
            }

            return trim((string) file_get_contents($file));
        }

        if ($this->option('ask-passphrase') && \function_exists('stream_isatty') && @stream_isatty(\STDIN)) {
            return trim((string) $this->secret('Object passphrase'));
        }

        return null;
    }
}
