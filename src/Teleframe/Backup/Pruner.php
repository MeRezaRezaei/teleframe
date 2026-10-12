<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use Throwable;

/**
 * Chunk garbage collection (night W1-3, P4-carried M): files deleted
 * between backup runs drop their chunks out of every manifest file
 * list, but the vault kept them forever — a shrinking set never
 * shrank its channel. Prune closes that: given the LATEST manifest's
 * chunk_hashes as the keep-set, every chunk-shaped vault entry outside
 * it is deleted.
 *
 * "Chunk-shaped" is exactly 64 lowercase hex chars (sha256 content
 * addresses as Chunker emits them) — checked with strlen/strspn, no
 * regex (src/ is regex-free by policy). Anything else (manifest
 * messages, foreign traffic) is structurally immune, and delete() on
 * the vault is idempotent, so repeated prunes are no-ops.
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
     * prune() PLUS what the lock actually did.
     *
     * The stats array is deliberately NOT extended with a lock key: prune()
     * returns exactly {scanned, pruned} and callers pin that shape, so the
     * lock verdict travels beside it instead. This matters because the
     * Redis-unavailable degradation (proceed WITHOUT the lock) used to be
     * reported through a PSR-3 warning that the engine's NullLogger default
     * swallows — the CLI could delete chunks of an in-flight backup and
     * print a clean success line. A console caller needs the verdict to
     * print it itself.
     *
     * @param  list<mixed>  $keepHashes
     * @return array{stats: array{scanned: int, pruned: int}, lock: array{acquired: bool, unavailable: bool}}
     */
    public static function pruneReportingLock(VaultInterface $vault, array $keepHashes, ?string $setId = null): array
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
            return ['stats' => ['scanned' => 0, 'pruned' => 0], 'lock' => $verdict];
        }

        try {
            $keep = self::keepSetFor($vault, $keepHashes);

            $inventory = method_exists($vault, 'listAllEntries')
                ? $vault->listAllEntries()
                : $vault->findMessagesByName('');

            $scanned = 0;
            $pruned = 0;
            foreach ($inventory as $entry) {
                $scanned++;
                $name = $entry['name'] ?? null;

                if (! is_string($name) || ! self::isChunkName($name) || isset($keep[$name])) {
                    continue;
                }

                $vault->delete($name);
                $pruned++;
            }

            return ['stats' => ['scanned' => $scanned, 'pruned' => $pruned], 'lock' => $verdict];
        } finally {
            if ($lock['acquired']) {
                BackupRunner::releaseSetLock($setId, (string) $lock['token']);
            }
        }
    }

    /**
     * The keep-set as NAMES ON THE WIRE.
     *
     * The caller passes the manifest's `chunk_hashes`, which are PLAINTEXT
     * content addresses; documents are named by their CIPHERTEXT hash (see
     * VaultInterface::putChunk), so comparing the two directly would make
     * every live chunk look like an orphan and delete the whole backup. A
     * vault that can resolve addresses (TelegramVault, InMemoryVault —
     * duck-typed, like the listAllEntries walk above) is asked to map them;
     * a vault that cannot is taken at its word, which is exactly right for
     * the older fakes that really do name documents by plaintext.
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
