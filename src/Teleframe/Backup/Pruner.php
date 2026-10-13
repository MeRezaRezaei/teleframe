<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use RuntimeException;
use Throwable;

/**
 * Chunk garbage collection (night W1-3, P4-carried M): files deleted
 * between backup runs drop their chunks out of every manifest file
 * list, but the vault kept them forever — a shrinking set never
 * shrank its channel. Prune closes that: given a KEEP-SET of chunk
 * hashes, every chunk-shaped vault entry outside it is deleted.
 *
 * RETENTION: the keep-set is NOT one generation wide any more. It is the
 * UNION of the chunk addresses of the newest N generations
 * (VaultInterface::listManifestChunkHashes, N from
 * teleframe.backup.keep_generations / --keep-generations), so a prune
 * leaves N generations restorable instead of exactly one. Keeping only
 * the newest made every older generation unrestorable BY CONSTRUCTION —
 * a daily prune therefore left one restorable generation and no safety
 * net, which is the wrong behaviour for a backup system however loudly
 * the CLI warned about it. Retention changes WHAT is kept; the
 * --force gate on WHETHER deletion happens is a separate, untouched
 * decision (see BackupCommand).
 *
 * "Chunk-shaped" is exactly 64 lowercase hex chars (sha256 content
 * addresses as Chunker emits them) — checked with strlen/strspn, no
 * regex (src/ is regex-free by policy). Anything else (manifest
 * messages, foreign traffic) is structurally immune, and delete() on
 * the vault is idempotent, so repeated prunes are no-ops.
 *
 * TWO PROPERTIES OF THE KEEP-SET ARE WORTH NAMING, because the CLI's
 * gate exists to make them visible rather than to change them:
 *
 *  1. It is derived from the RETAINED generations' manifests, so any
 *     generation outside the window loses its restorability. Pruner
 *     reports the doomed names so a caller can say which history the
 *     run costs.
 *  2. It is derived from the vault's CURRENT state, so it cannot know
 *     about chunks an IN-FLIGHT backup has uploaded but not yet
 *     published. Those are the shared per-set lock's problem, not the
 *     keep-set's — see pruneReportingLock() below.
 *
 * The inventory walk is duck-typed: a vault exposing listAllEntries()
 * (TelegramVault — an UNCAPPED getHistory pagination, because its
 * findMessagesByName is capped at SEARCH_LIMIT and would hide orphans
 * beyond the newest ~100 messages) gets the full walk; every other
 * VaultInterface implementation (InMemoryVault, test fakes) falls back
 * to findMessagesByName(''), which is already a complete listing.
 */
final class Pruner
{
    private const CHUNK_HASH_LENGTH = 64;

    private const HEX_DIGITS = '0123456789abcdef';

    /**
     * Clock trigger for the lock refresh during a prune walk.
     *
     * A prune that walks a large channel deletes one document per API round
     * trip, so the walk itself can run for hours — the same argument the
     * runner's own LOCK_REFRESH_DIVISOR makes for a long backup. Two hours is
     * the same fraction of the runner's 6 h TTL that the runner uses for
     * itself, which leaves room for the refresh to be missed twice before the
     * lock can actually lapse.
     */
    private const LOCK_REFRESH_SECONDS = 7200;

    /**
     * Entry-count trigger for the same refresh: a walk over a large inventory
     * can touch thousands of entries well inside the clock trigger, and a
     * prune that deletes hundreds of documents is exactly the run where a
     * lapsed lock does damage. Cheap — one extra Redis round trip per 100
     * entries.
     */
    private const LOCK_REFRESH_EVERY_ENTRIES = 100;

    /**
     * How many generations a retention keep-set spans when the caller
     * states nothing: the floor, not the recommendation. The SHIPPED
     * default lives in two places the operator actually reaches —
     * teleframe.backup.keep_generations and --keep-generations — and
     * both default to Pruner::RECOMMENDED_KEEP_GENERATIONS. One here
     * would be a silent third default that nothing can find.
     */
    public const DEFAULT_KEEP_GENERATIONS = 2;

    /**
     * The number the CLI and the config file ship as the default, and the
     * reason the interface floor above is not that number.
     *
     * WHY 3. Chunks are content-addressed and BackupRunner skips a chunk
     * the previous manifest already references, so an unchanged file
     * costs the SAME single document in every generation: retention N
     * does not multiply the set by N, it adds only the chunks that
     * DIFFER between the retained generations — roughly N-1 runs' worth
     * of actual change. Against that, the safety question is not "how
     * much does generation N-1 cost" but "how many bad runs can happen
     * before anyone notices": a corrupt or partial generation is not
     * detectable from the vault, only from a restore attempt, and the
     * attempt usually comes a day or two after the run. N=2 leaves one
     * fallback, so a single bad newest generation spends the whole
     * safety net at once and the previous fallback is then the only
     * thing between the operator and no backup. N=3 keeps two
     * independent fallbacks, which is the minimum for the feature to be
     * a safety net rather than a coin flip, while staying a bound an
     * operator can see and change in one config key.
     */
    public const RECOMMENDED_KEEP_GENERATIONS = 3;

    /**
     * @param  array<mixed>  $keepHashes  chunk_hashes of the latest manifest —
     *                                    raw manifest data, so non-string
     *                                    entries are ignored rather than trusted
     * @param  string|null  $setId  backup set id for the per-set lock key
     *                              (BackupRunner::LOCK_KEY_PREFIX). null —
     *                              the production caller passes none —
     *                              derives it from the latest manifest's
     *                              "set" field, which BackupRunner records.
     * @return array{scanned: int, pruned: int} scanned counts EVERY vault entry walked
     */
    public static function prune(VaultInterface $vault, array $keepHashes, ?string $setId = null): array
    {
        return self::pruneReportingLock($vault, $keepHashes, $setId)['stats'];
    }

    /**
     * THE RETENTION KEEP-SET: the UNION of the PLAINTEXT chunk addresses of
     * the newest $generations generations.
     *
     * UNION, and that is the whole correctness point. Two generations that
     * share a chunk name it ONCE, so a generation is never dropped for
     * "owning" a chunk another retained generation also owns, and no chunk
     * is counted twice. A keep-set built from the newest row alone is not a
     * narrower union — it is a different set, and it is what made every
     * older generation unrestorable.
     *
     * $latestHashes (the caller's own view of the newest manifest) is unioned
     * in as well, so a vault whose retention listing and whose
     * getLatestManifest() disagree cannot produce a keep-set narrower than
     * what the caller already held.
     *
     * WHERE THE TRUST BOUNDARY IS. Every vault filters non-string entries out
     * of a manifest's `chunk_hashes` before returning a row, so the row
     * shape here is the interface's DECLARED shape and is taken at its word.
     * A third-party vault that breaks the contract does not get a quietly
     * wrong keep-set — a non-string entry becomes an illegal array offset
     * and the prune fails loudly instead of deleting live chunks.
     *
     * @param  list<mixed>  $latestHashes
     * @return list<string> PLAINTEXT content addresses, de-duplicated
     */
    public static function retentionKeepHashes(VaultInterface $vault, array $latestHashes = [], int $generations = self::DEFAULT_KEEP_GENERATIONS): array
    {
        $keep = [];
        foreach ($latestHashes as $hash) {
            if (is_string($hash)) {
                $keep[$hash] = true;
            }
        }

        foreach ($vault->listManifestChunkHashes(max(1, $generations)) as $row) {
            foreach ($row['chunk_hashes'] as $hash) {
                $keep[$hash] = true;
            }
        }

        return array_keys($keep);
    }

    /**
     * prune() PLUS what the lock actually did, and WHICH chunks the prune
     * destroyed (or, on a dry run, WOULD destroy).
     *
     * The stats array is deliberately NOT extended with a lock key: prune()
     * returns exactly {scanned, pruned} and callers pin that shape, so the
     * lock verdict travels beside it instead. This matters because the
     * Redis-unavailable degradation (proceed WITHOUT the lock) used to be
     * reported through a PSR-3 warning that the engine's NullLogger default
     * swallows — the CLI could delete chunks of an in-flight backup and
     * print a clean success line. A console caller needs the verdict to
     * print it itself. The doomed names travel the same way, because a
     * count alone cannot tell an operator WHICH history this deletion just
     * cost them.
     *
     * $dryRun runs the identical counting pass and skips every delete(), so
     * the count a gate acts on is the count the real prune would produce —
     * the gate cannot be bypassed by a dry run disagreeing with reality.
     * The doomed names are collected either way, so a dry run can NAME what
     * it would destroy without destroying it.
     *
     * $keepHashes is the keep-set the CALLER resolved — with retention
     * that is Pruner::retentionKeepHashes() over N generations, not the
     * latest manifest's own list. The signature is unchanged on purpose:
     * widening it would break every existing caller for a value the caller
     * can compute through the public helper above.
     *
     * @param  list<mixed>  $keepHashes
     * @return array{stats: array{scanned: int, pruned: int}, lock: array{acquired: bool, unavailable: bool}, doomed: list<string>}
     */
    public static function pruneReportingLock(VaultInterface $vault, array $keepHashes, ?string $setId = null, bool $dryRun = false): array
    {
        $setId = $setId ?? self::setIdOf($vault);

        // Shared per-set lock (BackupRunner::acquireSetLock — the SAME key
        // the runner holds for the whole backup): a held lock means an
        // in-flight backup, so SKIP the prune entirely — never delete a
        // chunk the running backup may still (or will) reference. Redis
        // unavailable → documented degradation: proceed WITHOUT the lock
        // (see acquireSetLock()) — never crash the prune, but the caller
        // MUST be told: pruneReportingLock() carries the verdict out so a
        // console prints it, because the PSR-3 warning is swallowed by the
        // engine's NullLogger default.
        $lock = BackupRunner::acquireSetLock($setId);
        $verdict = ['acquired' => (bool) $lock['acquired'], 'unavailable' => (bool) $lock['unavailable']];

        if (! $lock['acquired'] && ! $lock['unavailable']) {
            return ['stats' => ['scanned' => 0, 'pruned' => 0], 'lock' => $verdict, 'doomed' => []];
        }

        // The refresh cadence is PRUNER-LOCAL, and deliberately so. The runner's
        // TTL and refresh interval are private to BackupRunner, and restating
        // them here would be a second copy of a security constant that can
        // drift. The TTL that is actually APPLIED to the key is never ours to
        // choose: it comes from BackupRunner::refreshSetLock(), which is the
        // same compare-and-extend script the runner uses. Only WHEN we call it
        // is local, and both triggers below sit far inside the runner's 6 h TTL.
        $lockToken = $lock['acquired'] ? (string) $lock['token'] : null;
        $refreshDueAt = time() + self::LOCK_REFRESH_SECONDS;
        $entriesSinceRefresh = 0;

        try {
            $keep = self::keepSetFor($vault, $keepHashes);

            $inventory = method_exists($vault, 'listAllEntries')
                ? $vault->listAllEntries()
                : $vault->findMessagesByName('');

            $scanned = 0;
            $pruned = 0;
            $doomed = [];
            foreach ($inventory as $entry) {
                $scanned++;

                // THE MISSING HEARTBEAT. This prune holds the SAME per-set lock
                // the runner holds for a whole backup, and that lock is the only
                // thing stopping a concurrent backup's freshly uploaded chunks —
                // which are in no published manifest, so not in this prune's
                // keep-set — from being deleted below. Without a refresh the lock
                // simply expires part-way through a long walk and the protection
                // evaporates SILENTLY, mid-deletion. Bounded by two triggers: the
                // clock (a slow walk where each entry is an API round trip) and
                // the entry count (a fast walk that finishes long before the
                // clock is due but still touches the lock repeatedly).
                if ($lockToken !== null) {
                    $entriesSinceRefresh++;
                    if (time() >= $refreshDueAt || $entriesSinceRefresh >= self::LOCK_REFRESH_EVERY_ENTRIES) {
                        self::refreshLockOrAbort($setId, $lockToken);
                        $refreshDueAt = time() + self::LOCK_REFRESH_SECONDS;
                        $entriesSinceRefresh = 0;
                    }
                }

                $name = $entry['name'] ?? null;

                if (! is_string($name) || ! self::isChunkName($name) || isset($keep[$name])) {
                    continue;
                }

                if (! $dryRun) {
                    $vault->delete($name);
                }
                $doomed[] = $name;
                $pruned++;
            }

            return ['stats' => ['scanned' => $scanned, 'pruned' => $pruned], 'lock' => $verdict, 'doomed' => $doomed];
        } finally {
            if ($lock['acquired']) {
                BackupRunner::releaseSetLock($setId, (string) $lock['token']);
            }
        }
    }

    /**
     * The keep-set as NAMES ON THE WIRE.
     *
     * The caller passes the keep-set's PLAINTEXT content addresses — under
     * retention that is retentionKeepHashes() over N generations, not one
     * manifest's own list. Documents are named by their CIPHERTEXT hash (see
     * VaultInterface::putChunk), so comparing the two directly would make
     * every live chunk look like an orphan and delete the whole backup. A
     * vault that can resolve addresses (TelegramVault, InMemoryVault,
     * S3DurableVault — duck-typed, like the listAllEntries walk above) is
     * asked to map them; a vault that cannot is taken at its word, which is
     * exactly right for the older fakes that really do name documents by
     * plaintext.
     *
     * The mapping is applied to the whole UNION at once, not per generation:
     * the address book is a property of the vault, and splitting the union
     * would resolve the same plaintext address through a different book for
     * each generation that carries it.
     *
     * A mapping failure is NOT swallowed: an unreadable address book means
     * the keep-set cannot be trusted, and a GC that guesses a keep-set
     * deletes live backups.
     *
     * @param  list<mixed>  $keepHashes
     * @return array<string, true>
     */
    private static function keepSetFor(VaultInterface $vault, array $keepHashes): array
    {
        $names = $keepHashes;
        if (method_exists($vault, 'wireNamesFor')) {
            $names = $vault->wireNamesFor($keepHashes);
        }

        $keep = [];
        foreach ($names as $name) {
            if (is_string($name)) {
                $keep[$name] = true;
            }
        }

        return $keep;
    }

    /**
     * The heartbeat's decision, mirroring BackupRunner's own pattern: a
     * proven-lost lock ABORTS the prune, anything else lets it continue.
     *
     * WHY ABORT AND NOT WARN. The lock is the only thing separating this
     * prune's deletes from a concurrent backup's in-flight chunks, and those
     * chunks are in no published manifest — so they are in no keep-set. Once
     * the lock is demonstrably gone, a second holder may exist, and the
     * protection this prune was performing no longer holds. Carrying on would
     * delete chunks a running backup is about to reference.
     *
     * A Redis BLIP does not abort: refreshSetLock() reports it as
     * `unavailable` rather than `lost` precisely because a failed round trip
     * is not proof of a lost lock, and the TTL remains the backstop. The
     * abort fires only on an explicit `lost`.
     *
     * @throws RuntimeException when the lock is demonstrably gone
     */
    private static function refreshLockOrAbort(string $setId, string $token): void
    {
        $refresh = BackupRunner::refreshSetLock($setId, $token);

        if (! $refresh['lost']) {
            return;
        }

        throw new RuntimeException(sprintf(
            'backup set "%s" LOST its lock during prune (the lock expired and was re-acquired, or was released by another process): '
            .'aborting rather than continuing unlocked. A backup may be in flight, and its not-yet-published chunks are in no '
            .'manifest, so this prune cannot see them as live and would delete them. Re-run the prune once the set is quiet.',
            $setId,
        ));
    }

    /**
     * Best-effort set id for the lock key when the caller omits it: the
     * latest manifest always records the set id it was backed up under
     * (BackupRunner writes manifest["set"]), so prune and run agree on the
     * lock key even though BackupCommand passes no set id to prune. A
     * manifest without a set field (or an unreadable one) falls back to
     * '' — the same key BackupRunner uses for a set-less config — keeping
     * the lock scoped to the whole vault rather than dropped entirely.
     */
    private static function setIdOf(VaultInterface $vault): string
    {
        try {
            $manifest = $vault->getLatestManifest();
            $setId = is_array($manifest) ? ($manifest['set'] ?? null) : null;

            return is_string($setId) ? $setId : '';
        } catch (Throwable) {
            return '';
        }
    }

    /** sha256 hex content address: exactly 64 lowercase hex chars (string functions only). */
    private static function isChunkName(string $name): bool
    {
        return strlen($name) === self::CHUNK_HASH_LENGTH
            && strspn($name, self::HEX_DIGITS) === self::CHUNK_HASH_LENGTH;
    }
}
