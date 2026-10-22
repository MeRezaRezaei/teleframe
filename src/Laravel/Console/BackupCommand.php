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

    /**
     * How many orphaned chunk names a prune report prints before it
     * summarises the rest. A display cap on the REPORT, never a cap on what
     * the prune deletes.
     */
    private const PRUNE_NAME_ROWS = 10;

    protected $signature = 'teleframe:backup
        {action : run|restore|verify|status|prune}
        {--set=default : Backup set id (key under teleframe.backup.sets)}
        {--passphrase-file= : Read the vault passphrase from this file (never an argument — argv is visible in `ps` and shell history)}
        {--target= : Restore target directory (restore action)}
        {--sample=5 : Chunk sample size for verify}
        {--dry-run : With `prune`, report what WOULD be pruned and delete nothing (this is also the default)}
        {--force : With `prune`, actually delete the orphaned chunks — required once the prune reports any}
        {--keep-generations= : With `prune`, how many newest generations stay restorable (default: teleframe.backup.keep_generations = 3; minimum 1)}
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
     * Chunk GC (night W1-3, P4-carried M): the keep-set is the union of
     * the RETAINED generations' chunk_hashes (the newest
     * `keepGenerations()` of them); every other chunk-shaped vault entry
     * (files deleted between runs) is deleted. Keyless — chunk names are
     * plaintext hashes — so it is cron-safe like plain verify.
     *
     * RETENTION, NOT ONE GENERATION. The keep-set used to be the LATEST
     * manifest alone, which made every older generation unrestorable by
     * construction: a daily prune left exactly one restorable generation
     * and no safety net. `keepGenerations()` — --keep-generations, else
     * teleframe.backup.keep_generations, else
     * Pruner::RECOMMENDED_KEEP_GENERATIONS — spans the union instead, and a
     * default of 1 would have made the whole feature a no-op by default.
     *
     * STILL BEHIND A GATE, because retention changed WHAT is kept and not
     * WHETHER deletion needs consent. The mechanism was documented — but
     * only on `status --list`, where an operator has gone looking. On the
     * prune path itself a run used to delete every chunk the newest
     * manifest did not reference and print a clean success line. An
     * irreversible, history-destroying action therefore reported itself
     * exactly like a routine one.
     *
     * WITHOUT `--force` this is a COUNTING PASS: the same walk, the same
     * count, and zero deletes. The count is what the gate acts on, and it
     * is the identical walk so the gate cannot be argued with — a dry run
     * that disagreed with a real prune would be worse than no gate.
     * `--dry-run` is the explicit spelling of that default, for scripts
     * and cron entries that want the report stated rather than implied.
     *
     * SCOPE, stated plainly: this fixes OVER-deletion (deleting more
     * than intended) by making the deletion opt-in and naming what it
     * costs. It does NOT fix UNDER-deletion — a vault that holds orphans
     * a prune cannot SEE (an inventory surface that hides them) still
     * keeps them. That is a separate defect with a separate cause.
     */
    private function pruneAction(string $setId): int
    {
        $vault = $this->vault($setId);
        $manifest = $vault->getLatestManifest();

        if ($manifest === null) {
            $this->error('prune needs a manifest first (run).');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $explicitDryRun = (bool) $this->option('dry-run');
        // No --force IS the counting pass; --dry-run only spells that default
        // out, so both reach Pruner with $dryRun = true and differ only in
        // how the refusal is worded.
        $dryRun = ! $force;

        if ($force && $explicitDryRun) {
            $this->error('--dry-run and --force contradict each other: one deletes nothing, the other authorises the deletion. Pass exactly one.');

            return self::FAILURE;
        }

        $keepGenerations = $this->keepGenerations();
        $latestHashes = $manifest['chunk_hashes'] ?? [];
        $latestHashes = is_array($latestHashes) ? array_values(array_filter($latestHashes, 'is_string')) : [];

        // The keep-set is the UNION over the retained generations, not the
        // newest manifest's own list — the fix for a prune that left exactly
        // one restorable generation. $latestHashes is unioned in as well (the
        // helper does it), so a keep-set can never come out NARROWER than
        // what the newest manifest already holds.
        $keepHashes = Pruner::retentionKeepHashes($vault, $latestHashes, $keepGenerations);

        // Restorability BEFORE the prune, which is the only thing that makes
        // the destruction reportable: a generation restorable now and
        // unrestorable after is one this run destroyed, and NOTHING in the
        // vault records which generations a given prune cost.
        $restorableBefore = $this->restorableGenerations($vault);

        $result = Pruner::pruneReportingLock($vault, $keepHashes, null, $dryRun);
        $prune = $result['stats'];

        // The Redis-unavailable degradation PROCEEDS WITHOUT the LOCK, which
        // is exactly the race the lock exists to prevent: chunks an in-flight
        // backup is still uploading can be deleted, the next manifest keeps
        // referencing them, and that set can never be restored. It used to
        // reach the operator through a PSR-3 warning alone — and the engine
        // binds NullLogger by default, so in production the prune printed a
        // clean success line and said nothing. Printed HERE, on the console,
        // with the same $this->warn convention the volatile-vault warning
        // above uses: no logger required, so it cannot be silenced.
        if ($result['lock']['unavailable']) {
            $this->warn(sprintf(
                'WARNING: prune set=%s ran WITHOUT the lock (Redis unavailable) — an in-flight backup may now reference deleted chunks.',
                $setId,
            ));
        }

        // The other lock verdict was silent too: a prune that skipped because
        // a backup holds the set reports scanned:0 / pruned:0, which reads
        // like a healthy no-op. It is a no-op with a cause, and the cause is
        // the whole reason the operator ran this.
        //
        // And it used to return SUCCESS over it. THE NO-WORK CONTRACT: the
        // lock verdict short-circuits BEFORE the inventory walk
        // (Pruner::pruneReportingLock returns scanned:0 without touching a
        // single entry), so `pruned:0` here is not a count — it is the absence
        // of a prune. A cron shelling out to prune read "retention is keeping
        // up" for a retention pass that never ran. It is a FAILURE, and it is
        // distinguished from the lock-UNAVAILABLE case above (Redis down), where
        // the prune really did walk and really did count, and where zero is a
        // true answer that stays SUCCESS.
        if (! $result['lock']['acquired'] && ! $result['lock']['unavailable']) {
            $this->error(sprintf(
                'SKIPPED: prune set=%s was NOT run — a backup holds the set lock, so nothing was scanned and nothing was deleted. '
                .'Re-run once that backup finishes; this exits non-zero so a cron does not read a prune that never ran as a clean one.',
                $setId,
            ));
        }

        $this->line(sprintf('prune set=%s {scanned:%d, pruned:%d}', $setId, $prune['scanned'], $prune['pruned']));

        if (! $result['lock']['acquired'] && ! $result['lock']['unavailable']) {
            return self::FAILURE;
        }

        if ($prune['pruned'] === 0) {
            return self::SUCCESS;
        }

        $this->reportDoomedChunks($setId, $result['doomed'], $force);
        $destroyed = $this->reportDestroyedGenerations($setId, $vault, $restorableBefore, $force, $keepGenerations);

        if ($force) {
            return self::SUCCESS;
        }

        // Both refusals exit non-zero, and both say so: either the operator
        // asked for a prune and did not get one, or they asked for a report
        // and the report's answer is "there is pending destruction". A cron
        // that shells out to either gets a non-zero code for work that is
        // waiting on a decision, which is the state worth alerting on.
        if ($explicitDryRun) {
            $this->error(sprintf(
                'DRY RUN: prune set=%s found %d orphaned chunk(s) and up to %d generation(s) at risk. Nothing was deleted. '
                .'Re-run with --force (and without --dry-run) to apply.',
                $setId,
                $prune['pruned'],
                count($destroyed),
            ));

            return self::FAILURE;
        }

        $this->error(sprintf(
            'REFUSED: prune set=%s would DELETE %d orphaned chunk(s) and cost %d generation(s) their restorability. '
            .'Nothing was deleted. Re-run with --force to accept that, or with --dry-run to repeat this report without deleting anything.',
            $setId,
            $prune['pruned'],
            count($destroyed),
        ));

        return self::FAILURE;
    }

    /**
     * NAME the orphaned chunks a prune destroyed (or would destroy).
     *
     * A count alone cannot tell an operator what was lost, and the names are
     * the only record that will ever exist of them: once the documents are
     * gone the vault cannot list them. Capped by PRUNE_NAME_ROWS for width —
     * a display cap on the REPORT, never a cap on what the prune deletes.
     *
     * @param  list<string>  $doomed
     */
    private function reportDoomedChunks(string $setId, array $doomed, bool $deleted): void
    {
        $this->line(sprintf(
            '%s set=%s {orphaned_chunks:%d}',
            $deleted ? 'prune-deleted' : 'prune-would-delete',
            $setId,
            count($doomed),
        ));

        foreach (array_slice($doomed, 0, self::PRUNE_NAME_ROWS) as $name) {
            $this->line(sprintf('  orphan chunk %s', $name));
        }

        $rest = count($doomed) - self::PRUNE_NAME_ROWS;
        if ($rest > 0) {
            $this->line(sprintf('  ... and %d more', $rest));
        }
    }

    /**
     * Which generations this prune destroyed, BY GENERATION NUMBER, and the
     * warning that says so out loud.
     *
     * After a real prune the answer is exact: a generation restorable before
     * and unrestorable after is one this run destroyed. A generation that
     * shared every chunk with a RETAINED generation is still restorable, and
     * is correctly NOT reported — the keep-set is chunk-shaped, so "older
     * generations are not kept" is a statement about chunks, and a
     * generation that happens to share all of them survives.
     *
     * On a counting pass there is no "after" to compare against, so the
     * at-risk set is reported as what it is: every currently-restorable
     * generation that falls OUTSIDE the retention window (the newest
     * $keepGenerations), minus the shared-chunk exception, which is named
     * rather than silently folded in. Claiming an exact count the counting
     * pass cannot compute would be the same class of defect as printing a
     * clean success line over a destructive action.
     *
     * @param  array<int, bool>  $restorableBefore  generation => restorable, before the prune
     * @return list<int> generations destroyed (or, on a counting pass, at risk)
     */
    private function reportDestroyedGenerations(string $setId, VaultInterface $vault, array $restorableBefore, bool $deleted, int $keepGenerations = 1): array
    {
        if ($restorableBefore === []) {
            return [];
        }

        // The RETAINED window: the newest $keepGenerations generation numbers,
        // newest first. A generation in it is inside the keep-set by
        // construction; anything older is at risk on a counting pass.
        $ordered = array_keys($restorableBefore);
        rsort($ordered);
        $retained = array_slice($ordered, 0, max(1, $keepGenerations));

        $restorableAfter = $deleted ? $this->restorableGenerations($vault) : [];

        $destroyed = [];
        foreach ($restorableBefore as $generation => $wasRestorable) {
            if (! $wasRestorable) {
                // Already unrestorable before this run — not this prune's doing.
                continue;
            }

            if ($deleted) {
                if (($restorableAfter[$generation] ?? false) === true) {
                    continue; // shared every chunk with a retained generation
                }

                $destroyed[] = $generation;

                continue;
            }

            if (! in_array($generation, $retained, true)) {
                $destroyed[] = $generation;
            }
        }

        if ($destroyed === []) {
            return [];
        }

        sort($destroyed);

        if ($deleted) {
            $this->warn(sprintf(
                'WARNING: prune set=%s DESTROYED %d generation(s) (gen %s) — a prune keeps the newest %d generation(s), so those '
                .'generations can no longer be restored.',
                $setId,
                count($destroyed),
                implode(', ', $destroyed),
                $keepGenerations,
            ));

            return $destroyed;
        }

        $this->warn(sprintf(
            'WARNING: prune set=%s WOULD destroy up to %d generation(s) (gen %s) — a prune keeps the newest %d generation(s), so '
            .'those generations could no longer be restored once --force is used. Any generation that shares EVERY chunk with a '
            .'retained generation survives.',
            $setId,
            count($destroyed),
            implode(', ', $destroyed),
            $keepGenerations,
        ));

        return $destroyed;
    }

    /**
     * HOW MANY newest generations a prune keeps restorable.
     *
     * `--keep-generations` overrides `teleframe.backup.keep_generations`,
     * which overrides Pruner::RECOMMENDED_KEEP_GENERATIONS. The shipped
     * default is that constant's 3, NOT 1: a default of 1 makes the whole
     * retention feature a no-op on an unconfigured install, which is worse
     * than not having it — an operator would have to know the key exists to
     * get any safety net at all.
     *
     * A value below 1 is RAISED to 1, never honoured as 0. "Keep zero
     * generations" is a phrase with exactly one meaning — delete every chunk
     * in the set, leaving manifests that reference nothing — and it is not
     * something an operator should be able to reach by typing a small
     * number at a flag without also saying --force on the same command.
     */
    private function keepGenerations(): int
    {
        $option = (string) ($this->option('keep-generations') ?? '');
        $configured = $option !== ''
            ? $option
            : (string) $this->getLaravel()->make('config')->get(
                'teleframe.backup.keep_generations',
                Pruner::RECOMMENDED_KEEP_GENERATIONS,
            );

        return max(1, (int) $configured);
    }

    /**
     * generation => restorable, as the vault itself reports it.
     *
     * Capped at the same width as `status --list`: this is a diagnostic about
     * the newest generations, and the generations a prune can destroy are by
     * definition older than the newest, so a wider walk would cost a full
     * channel inventory to report rows that are already known-safe.
     *
     * @return array<int, bool>
     */
    private function restorableGenerations(VaultInterface $vault): array
    {
        $unreadable = [];
        $rows = $vault->listManifests(self::HISTORY_ROWS + 1, $unreadable);

        $restorable = [];
        foreach ($rows as $row) {
            $restorable[(int) $row['generation']] = (bool) $row['restorable'];
        }

        return $restorable;
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
                .'a prune deleted them, so those generations CANNOT be restored. A prune keeps the newest %d generation(s) BY '
                .'DEFAULT (teleframe.backup.keep_generations); a run that passed --keep-generations kept a different number.',
                $setId,
                count($unrestorable),
                implode(', ', $unrestorable),
                $this->keepGenerations(),
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
