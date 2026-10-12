<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\Pruner;
use MeRezaRezaei\Teleframe\Backup\Restorer;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Backup\Verifier;
use RuntimeException;

/**
 * artisan teleframe:backup (plan Phase 4, Task 4): the CLI shell
 * over BackupRunner / Restorer / Verifier for one configured backup set
 * (config teleframe.backup.sets.<set>).
 *
 * Vault transport is a container seam mirroring BackfillCommand:
 * VAULT_FACTORY_KEY binds callable(string $setId): VaultInterface. The
 * provider default honours teleframe.backup.driver — 'memory'
 * (offline default; a per-set shared InMemoryVault, so run→restore works
 * inside one process but nothing is stored) or 'telegram', which resolves
 * a real scope through the P3 SCOPE_RESOLVER_KEY seam (the shared
 * daemon.accounts registry via AccountWorker::buildLiveScope) and builds
 * a TelegramVault over it.
 *
 * The passphrase is NEVER an argument. A shell argument lands in the shell
 * history and in `ps` for every user on the box, and it is the key to every
 * sealed object in the set. It is read, in order, from
 * TELEFRAME_BACKUP_PASSPHRASE (the cron path), --passphrase-file=PATH, or a
 * hidden STDIN prompt — the same shape teleframe:passport import uses for an
 * exported authorization. Nothing echoes it, and the prompt is hidden.
 */
final class BackupCommand extends Command
{
    /** Container seam: callable(string $setId): VaultInterface (provider binds the driver-aware default). */
    public const VAULT_FACTORY_KEY = 'teleclient.backup.vault-factory';

    private const ACTIONS = ['run', 'restore', 'verify', 'status', 'prune'];

    /**
     * Default staleness bound in HOURS when teleframe.backup.max_age_hours
     * is absent: a day. A daily backup that misses one cycle is a red
     * status, which is the point — the bound is a SLA, not a warning.
     */
    public const DEFAULT_MAX_AGE_HOURS = 24;

    /**
     * How many generations `status --list` prints. A plain literal, NOT a
     * config key: teleframe.backup already has max_age_hours and a list
     * bound is a display width, and every declared key must be classified
     * in tests/Config/DeclaredKeyCoverageTest.php.
     */
    private const HISTORY_ROWS = 10;

    protected $signature = 'teleframe:backup
        {action : run|restore|verify|status|prune}
        {--set=default : Backup set id (key under teleframe.backup.sets)}
        {--passphrase-file= : Read the vault passphrase from this file (never an argument — argv is visible in `ps` and shell history)}
        {--target= : Restore target directory (restore action)}
        {--sample=5 : Chunk sample size for verify}
        {--list : With `status`, print the backup HISTORY (one line per generation, newest first) as well as freshness}';

    protected $description = 'Encrypted Telegram-channel backup vault: run, restore, verify or show status of a backup set';

    protected $aliases = [];

    public function handle(): int
    {
        $action = (string) $this->argument('action');

        if (! in_array($action, self::ACTIONS, true)) {
            $this->error(sprintf('unknown action "%s" — expected run|restore|verify|status|prune.', $action));

            return self::FAILURE;
        }

        $setId = (string) $this->option('set');

        /** @var array<string, mixed>|null $setConfig */
        $setConfig = $this->getLaravel()->make('config')->get('teleframe.backup.sets.'.$setId);

        if (! is_array($setConfig)) {
            $this->error(sprintf('unknown backup set "%s" — configure teleframe.backup.sets.%s.', $setId, $setId));

            return self::FAILURE;
        }

        try {
            return match ($action) {
                'run' => $this->runAction($setId, $setConfig),
                'restore' => $this->restoreAction($setId),
                'verify' => $this->verifyAction($setId),
                'prune' => $this->pruneAction($setId),
                default => $this->statusAction($setId),
            };
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string, mixed> $setConfig */
    private function runAction(string $setId, array $setConfig): int
    {
        $vault = $this->vault($setId);

        $this->assertPathsConfigured($setId, $setConfig);
        $this->assertVaultPersists($setId, $vault);
        $this->assertManifestSecretConfigured();

        $stats = BackupRunner::run(
            $setConfig + [
                'set' => $setId,
                'chunk_size' => (int) $this->getLaravel()->make('config')->get('teleframe.backup.chunk_size', 4194304),
            ],
            $vault,
            $this->passphrase('run'),
        );

        $this->info(sprintf(
            'backup set=%s {uploaded:%d, skipped:%d, files:%d, bytes:%d}',
            $setId,
            $stats['uploaded'],
            $stats['skipped'],
            $stats['files'],
            $stats['bytes'],
        ));

        return self::SUCCESS;
    }

    /**
     * The shipped `default` set has an empty `paths` list, and a backup with
     * nothing to back up is a configuration mistake, not a crash: name the
     * key to fill instead of letting BackupRunner throw
     * "setConfig must list at least one path" from four frames down.
     *
     * @param  array<string, mixed>  $setConfig
     */
    private function assertPathsConfigured(string $setId, array $setConfig): void
    {
        $paths = $setConfig['paths'] ?? null;
        $paths = is_array($paths) ? array_values(array_filter($paths, static fn ($p): bool => is_string($p) && $p !== '')) : [];

        if ($paths !== []) {
            return;
        }

        throw new RuntimeException(sprintf(
            'backup set "%s" has no paths: nothing to back up. List at least one path or directory under '
            .'teleframe.backup.sets.%s.paths (e.g. base_path("storage/app")), or point --set= at a set that has some. '
            .'A backup that captures no files is not a backup, so this refuses rather than reporting success.',
            $setId,
            $setId,
        ));
    }

    /**
     * A process-local vault is a legitimate OFFLINE TEST double and a
     * catastrophic default: it reported a green "backup set=… {uploaded:N}"
     * while nothing left the process. It stays available, but only to a host
     * that acknowledged it in configuration
     * (teleframe.backup.allow_volatile_vault) — and even then the run says
     * so out loud. The secret is never printed.
     */
    private function assertVaultPersists(string $setId, VaultInterface $vault): void
    {
        if (! $vault instanceof InMemoryVault) {
            return;
        }

        if (! (bool) $this->getLaravel()->make('config')->get('teleframe.backup.allow_volatile_vault', false)) {
            throw new RuntimeException(sprintf(
                'refusing to run backup set "%s" against the "memory" driver: it is a PROCESS-LOCAL vault that stores '
                .'NOTHING — every chunk and the manifest die with this command, so a green run here is a backup that '
                .'does not exist. Point TELEFRAME_BACKUP_DRIVER at a durable store ("telegram" is the shipped default '
                .'and needs TELEFRAME_BACKUP_ACCOUNT), or set TELEFRAME_BACKUP_ALLOW_VOLATILE=1 to acknowledge the '
                .'volatile vault deliberately (tests only).',
                $setId,
            ));
        }

        $this->warn(sprintf(
            'WARNING: backup set=%s is running on the "memory" driver — this run stores NOTHING and survives no process.',
            $setId,
        ));
    }

    private function restoreAction(string $setId): int
    {
        $target = (string) ($this->option('target') ?? '');

        if ($target === '') {
            $this->error('restore needs --target=<directory>.');

            return self::FAILURE;
        }

        $stats = Restorer::restore($this->vault($setId), $this->passphrase('restore'), $target);

        $this->info(sprintf('restored set=%s {files:%d, bytes:%d} -> %s', $setId, $stats['files'], $stats['bytes'], $target));

        return self::SUCCESS;
    }

    private function verifyAction(string $setId): int
    {
        $vault = $this->vault($setId);
        $sample = max(1, (int) $this->option('sample'));

        $passphrase = $this->passphraseIfGiven() ?? '';

        if ($passphrase !== '') {
            $result = Verifier::verifyWithKey($vault, $passphrase, $sample);
            $this->line(sprintf(
                'verify set=%s {checked:%d, ok:%d, missing:%d, corrupt:%d}',
                $setId,
                $result['checked'],
                $result['ok'],
                $result['missing'],
                $result['corrupt'],
            ));

            return $result['missing'] > 0 || $result['corrupt'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $result = Verifier::verify($vault, $sample);
        $this->line(sprintf(
            'verify set=%s {checked:%d, ok:%d, missing:%d, corrupt:%d}',
            $setId,
            $result['checked'],
            $result['ok'],
            $result['missing'],
            $result['corrupt'],
        ));

        return $result['missing'] > 0 || $result['ok'] < $result['checked'] ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Chunk GC (night W1-3, P4-carried M): the latest manifest's
     * chunk_hashes is the keep-set; every other chunk-shaped vault entry
     * (files deleted between runs) is deleted. Keyless — chunk names are
     * plaintext hashes — so it is cron-safe like plain verify.
     */
    private function pruneAction(string $setId): int
    {
        $vault = $this->vault($setId);
        $manifest = $vault->getLatestManifest();

        if ($manifest === null) {
            $this->error('prune needs a manifest first (run).');

            return self::FAILURE;
        }

        $keepHashes = $manifest['chunk_hashes'] ?? [];
        $keepHashes = is_array($keepHashes) ? array_values(array_filter($keepHashes, 'is_string')) : [];

        $stats = Pruner::pruneReportingLock($vault, $keepHashes);
        $prune = $stats['stats'];

        // The Redis-unavailable degradation PROCEEDS WITHOUT the LOCK, which
        // is exactly the race the lock exists to prevent: chunks an in-flight
        // backup is still uploading can be deleted, the next manifest keeps
        // referencing them, and that set can never be restored. It used to
        // reach the operator through a PSR-3 warning alone — and the engine
        // binds NullLogger by default, so in production the prune printed a
        // clean success line and said nothing. Printed HERE, on the console,
        // with the same $this->warn convention the volatile-vault warning
        // above uses: no logger required, so it cannot be silenced.
        if ($stats['lock']['unavailable']) {
            $this->warn(sprintf(
                'WARNING: prune set=%s ran WITHOUT the lock (Redis unavailable) — an in-flight backup may now reference deleted chunks.',
                $setId,
            ));
        }

        $this->line(sprintf('prune set=%s {scanned:%d, pruned:%d}', $setId, $prune['scanned'], $prune['pruned']));

        return self::SUCCESS;
    }

    /**
     * Staleness, not inventory. `status` used to print the manifest's
     * `created` and then return SUCCESS unconditionally — nothing in the
     * codebase compared that timestamp to now, so a cron that had been
     * failing for months produced no line, no exit code, and never even
     * reached the NullLogger default. A backup that is not being taken is
     * the failure nobody notices, so age is now evaluated and exceeds the
     * bound -> FAILURE, with the age printed so the operator sees WHY.
     */
    private function statusAction(string $setId): int
    {
        $vault = $this->vault($setId);
        $manifest = $vault->getLatestManifest();

        if ((bool) $this->option('list')) {
            $this->listHistory($setId, $vault);
        }

        if ($manifest === null) {
            $this->line(sprintf('status set=%s: no manifest yet (run first)', $setId));
            $this->error(sprintf(
                'STALE: set=%s has NO manifest at all — no backup has ever been written for it, so there is nothing to restore. '
                .'Either this set has never been run or every run failed. A set with no backup is not a healthy set, so status '
                .'fails rather than reporting a green no-manifest.',
                $setId,
            ));

            return self::FAILURE;
        }

        $entries = $manifest['files'] ?? null;
        $entries = is_array($entries) ? $entries : [];

        $bytes = 0;
        foreach ($entries as $entry) {
            $bytes += is_array($entry) && is_int($entry['size'] ?? null) ? $entry['size'] : 0;
        }

        $chunks = $manifest['chunk_hashes'] ?? [];
        $chunks = is_array($chunks) ? count($chunks) : 0;

        $created = is_string($manifest['created'] ?? null) ? (string) $manifest['created'] : 'unknown';

        // gmdate('c') by BackupRunner:220, so an ISO-8601 instant. strtotime
        // keeps this regex-free like the rest of the backup module.
        $createdAt = $created === 'unknown' ? false : strtotime($created);
        $ageHours = $createdAt === false ? null : (int) floor(max(0, time() - $createdAt) / 3600);

        $maxAgeHours = $this->maxAgeHours();

        $this->line(sprintf(
            'status set=%s {files:%d, bytes:%d, chunks:%d, created:%s, age_hours:%s, max_age_hours:%d}',
            $setId,
            count($entries),
            $bytes,
            $chunks,
            $created,
            $ageHours === null ? 'unknown' : (string) $ageHours,
            $maxAgeHours,
        ));

        if ($ageHours === null) {
            $this->error(sprintf(
                'STALE: set=%s manifest has no readable `created` timestamp (%s), so its freshness cannot be proven. '
                .'An unprovable backup is treated as a stale one.',
                $setId,
                $created,
            ));

            return self::FAILURE;
        }

        if ($ageHours > $maxAgeHours) {
            $this->error(sprintf(
                'STALE: the newest manifest for set=%s is %d h old, over the %d h bound (teleframe.backup.max_age_hours). '
                .'The backup is not being refreshed — every age above the bound means the run is failing or not scheduled.',
                $setId,
                $ageHours,
                $maxAgeHours,
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The backup HISTORY behind `--list`, in the same `set=… {k:v}` line
     * style the rest of this command uses.
     *
     * A set's depth was unanswerable: status could prove the NEWEST
     * manifest is fresh and then stop, so an operator who wanted to know
     * how many generations exist, how old the oldest is, or whether an old
     * generation can still be restored had no surface at all. Rows are
     * ordered by the sealed generation, not by message id, and each carries
     * that generation's OWN counts.
     *
     * A generation whose chunks a later prune deleted CANNOT be restored.
     * That is the most valuable thing this listing can say, so it is
     * flagged on the row AND as a warning, and the flag is a hard
     * assertion: it compares the manifest's own chunk address list against
     * the documents the vault still holds.
     */
    private function listHistory(string $setId, VaultInterface $vault): void
    {
        $unreadable = [];
        $rows = $vault->listManifests(self::HISTORY_ROWS + 1, $unreadable);
        $truncated = count($rows) > self::HISTORY_ROWS;
        if ($truncated) {
            $rows = array_slice($rows, 0, self::HISTORY_ROWS);
        }

        $this->line(sprintf('history set=%s {generations:%d}', $setId, count($rows)));
        if ($truncated) {
            $this->warn(sprintf(
                'history set=%s: showing the newest %d generations only — the set holds more.',
                $setId,
                self::HISTORY_ROWS,
            ));
        }

        $unrestorable = [];
        $totalFiles = 0;
        $totalChunks = 0;
        $totalBytes = 0;

        foreach ($rows as $row) {
            $totalFiles += $row['files'];
            $totalChunks += $row['chunks'];
            $totalBytes += $row['bytes'];

            $this->line(sprintf(
                '  gen=%d created=%s files=%d chunks=%d bytes=%d restorable=%s missing_chunks=%d',
                $row['generation'],
                $row['created'],
                $row['files'],
                $row['chunks'],
                $row['bytes'],
                $row['restorable'] ? 'yes' : 'NO',
                $row['missing_chunks'],
            ));

            if (! $row['restorable']) {
                $unrestorable[] = $row['generation'];
            }
        }

        if ($rows === []) {
            $this->line('  (no readable manifest generations)');
        }

        $oldest = $rows === [] ? null : $rows[count($rows) - 1];
        $this->line(sprintf(
            'history-total set=%s {generations:%d, files:%d, chunks:%d, bytes:%d, oldest_generation:%s, oldest_created:%s}',
            $setId,
            count($rows),
            $totalFiles,
            $totalChunks,
            $totalBytes,
            $oldest === null ? 'none' : (string) $oldest['generation'],
            $oldest === null ? 'none' : $oldest['created'],
        ));

        if ($unrestorable !== []) {
            $this->warn(sprintf(
                'WARNING: set=%s has %d generation(s) referencing chunks the vault no longer holds (generation %s) — '
                .'a prune deleted them, so those generations CANNOT be restored. Only the newest generation is kept by a prune.',
                $setId,
                count($unrestorable),
                implode(', ', $unrestorable),
            ));
        }

        if ($unreadable !== []) {
            $this->warn(sprintf(
                'WARNING: set=%s has %d manifest post(s) that could not be read (message ids %s) — they are omitted from the '
                .'history above, not shown as a gap-free list.',
                $setId,
                count($unreadable),
                implode(', ', $unreadable),
            ));
        }
    }

    /**
     * The staleness bound in HOURS. Read defensively: the shipped default
     * (24) is applied when the key is absent, so a host that never
     * published the key still gets the check rather than an unconfigured
     * "0 = everything is stale".
     */
    private function maxAgeHours(): int
    {
        $configured = $this->getLaravel()->make('config')->get('teleframe.backup.max_age_hours', self::DEFAULT_MAX_AGE_HOURS);

        return max(0, (int) $configured);
    }

    private function vault(string $setId): VaultInterface
    {
        $factory = $this->getLaravel()->make(self::VAULT_FACTORY_KEY);

        if (! is_callable($factory) || is_string($factory)) {
            throw new RuntimeException(self::VAULT_FACTORY_KEY.' must bind a callable(string): VaultInterface');
        }

        return $factory($setId);
    }

    /**
     * The manifest INDEX is sealed by the vault with a key derived from
     * teleframe.backup.passphrase (env TELEFRAME_BACKUP_PASSPHRASE) — a CONFIG
     * value, because a set written now must be readable by every later
     * process (restore from a fresh shell, a cron prune, an incident). A
     * passphrase typed at this command's hidden prompt, or handed over in a
     * file, seals the CHUNKS only; the index would then be written under a key
     * this process does not have and the set could never be read back. Say so
     * here rather than letting the vault throw from four frames down.
     *
     * EVERY DURABLE TIER IS COVERED, NOT JUST TELEGRAM (Cycle 39 — Goal F).
     * This returned early for every driver but `telegram`, so an operator
     * running `--driver=s3` with no passphrase was told NOTHING while that
     * method's own docblock stated as a universal fact that the index is
     * sealed. On this tier the unsealed index is not merely a disclosure
     * limit, it is a write primitive: the `generation` S3DurableVault ranks
     * "latest" by is a bare int an injector into the bucket can set to
     * PHP_INT_MAX. `memory` stays the one exempt driver, and it is exempt
     * because it is process-local — nothing outlives the run that wrote it,
     * so there is no index for a later process to read back.
     */
    private function assertManifestSecretConfigured(): void
    {
        $config = $this->getLaravel()->make('config');

        $driver = (string) ($config->get('teleframe.backup.driver') ?: 'telegram');
        if ($driver === 'memory') {
            return;
        }

        if ((string) ($config->get('teleframe.backup.passphrase') ?: '') !== ''
            || (string) ($config->get('teleframe.tstore.passphrase') ?: '') !== '') {
            return;
        }

        throw new RuntimeException(
            "backup driver \"{$driver}\" needs the vault passphrase in CONFIG (TELEFRAME_BACKUP_PASSPHRASE -> "
            .'teleframe.backup.passphrase): it seals the manifest index, which every later process — a restore from a '
            .'fresh shell, a cron prune — must be able to read back. A --passphrase-file or hidden prompt seals the '
            .'chunks only, so the set would be written and then be unreadable.',
        );
    }

    /**
     * The vault passphrase, from TELEFRAME_BACKUP_PASSPHRASE (cron), a
     * --passphrase-file, or a HIDDEN STDIN prompt — never from argv.
     * Returns null when the caller is non-interactive and nothing is
     * configured, so `verify` can still run its keyless pass.
     */
    private function passphraseIfGiven(): ?string
    {
        $fromEnv = (string) (getenv('TELEFRAME_BACKUP_PASSPHRASE') ?: '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $file = (string) ($this->option('passphrase-file') ?? '');
        if ($file !== '') {
            $contents = @file_get_contents($file);
            if ($contents === false) {
                throw new RuntimeException('cannot read --passphrase-file (unreadable path).');
            }

            return rtrim($contents, "\r\n");
        }

        // A human at a real terminal gets a hidden prompt; a cron job, a pipe
        // or a test run must NEVER block on one.
        if ($this->input->isInteractive() && $this->hasTerminal()) {
            $typed = (string) $this->secret('vault passphrase');
            if ($typed !== '') {
                return $typed;
            }
        }

        return null;
    }

    /** Whether a real terminal is attached to STDIN. */
    private function hasTerminal(): bool
    {
        if (PHP_SAPI !== 'cli' || ! defined('STDIN')) {
            return false;
        }

        return @stream_isatty(STDIN);
    }

    /** The passphrase for an action that cannot run without one. */
    private function passphrase(string $action): string
    {
        $passphrase = $this->passphraseIfGiven();

        if ($passphrase === null || $passphrase === '') {
            throw new RuntimeException(
                "{$action} needs the vault passphrase: set TELEFRAME_BACKUP_PASSPHRASE, pass --passphrase-file=PATH, "
                .'or type it at the hidden prompt. It is never accepted as an argument — argv is world-readable in `ps`.'
            );
        }

        return $passphrase;
    }
}
