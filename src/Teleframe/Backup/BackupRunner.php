<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\ChunkPlanner;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Scan a backup set → content-addressed chunks → encrypt → dedup against
 * the vault's CURRENT manifest → upload unique chunks → post the new
 * manifest LAST (plan Phase 4: manifest-first restore, no server index).
 *
 * Two addresses per chunk, and the distinction is the point:
 *
 *  - The PLAINTEXT sha256 (Chunker) is the content address used for
 *    DEDUPLICATION and recorded in the manifest's `files[].chunks` /
 *    `chunk_hashes`. secretstream encryption is non-deterministic (a fresh
 *    random header per call), so ciphertext addressing would defeat
 *    cross-run dedup — but the plaintext address must never appear on the
 *    CHANNEL, because a document named by its plaintext hash is a
 *    block-level chosen-plaintext confirmation oracle for anyone who can
 *    list the channel, at public 4 MiB chunk boundaries. (TStore has
 *    always documented this: ObjectManifest carries wire_sha256 for exactly
 *    this reason.)
 *  - The CIPHERTEXT sha256 is the wire/document name. The mapping
 *    ciphertext → plaintext travels in `wire_chunks` INSIDE the sealed
 *    manifest, so the address book is as protected as the file tree and
 *    the KDF salt beside it.
 *
 * The manifest also carries a monotonic `generation`: TelegramVault reads
 * the highest generation rather than the newest message id, so a rollback
 * to an older manifest loses instead of becoming truth.
 */
final class BackupRunner
{
    public const MANIFEST_VERSION = 1;

    /**
     * Per-set backup lock key prefix — one lock per backup set, SHARED
     * with Pruner (chunk GC) so a prune never deletes chunks of an
     * in-flight backup. Namespace matches the bus keys (tg:stream:*,
     * tg:bus:*).
     */
    public const LOCK_KEY_PREFIX = 'tg:backup:lock:';

    /** Crash-safe lock TTL: a holder that dies without releasing wedges the set for at most this long. */
    private const LOCK_TTL_SECONDS = 14400;

    /** Compare-and-delete release: only the current holder may drop its own lock. */
    private const RELEASE_SCRIPT = <<<'LUA'
if redis.call('GET', KEYS[1]) == ARGV[1] then
  return redis.call('DEL', KEYS[1])
end
return 0
LUA;

    /** Redis client for the per-set backup lock; shared with Pruner. Seam first, else lazily resolved. */
    private static PhpRedisConnection|PredisConnection|null $lockConnection = null;

    /** PSR-3 target for lock degradation warnings; null = the engine's silent (NullLogger) default. */
    private static ?LoggerInterface $lockLogger = null;

    /**
     * Default content-addressed chunk size, in BYTES: 4 MiB
     * (4 * 1024 * 1024 = 4194304).
     *
     * NOT 4 GiB, and deliberately not. The owner's "it make files into 4 gig
     * chunks" (verbatim 2026-09-24, docs/verbatim/2026-09-24-notification-
     * logger-backup-and-bot-framework.md:40) is capability S8, and S8 is
     * TStore: `Store\ChunkPlanner`, where a 4 GiB chunk is a real
     * *document* uploaded as 512 KiB parts and streamed part-by-part off a
     * seekable handle — `ChunkPlanner::planChunks`/`planParts`/`readPart`
     * (PREMIUM_CAP = 512 KiB * 8000 = 4 GiB, FREE_CAP = 2 GiB). That
     * capability is already honoured there; it was never this constant's job.
     *
     * This module is a different capability with different mechanics, so the
     * two sizes are legitimately different numbers:
     *
     *  1. A chunk here is NOT a document inside a larger document — it IS
     *     one whole Telegram message (TelegramVault::putChunk ->
     *     sendDocument). Message count == chunk count by construction, so
     *     raising this to 4 GiB would not "produce one message instead of
     *     a thousand"; it would produce a thousand messages either way, just
     *     1000x more RAM per message.
     *  2. This path is NOT streaming. BackupRunner::run() slurps each file
     *     whole (file_get_contents), Chunker::split materialises every
     *     chunk of it simultaneously, and each chunk is then encrypted
     *     before upload. A 4 GiB chunk size means several live 4 GiB
     *     buffers per file — an OOM, not a saving.
     *  3. The chunk is the dedup key. A small chunk dedups finer: a
     *     one-line edit re-uploads one chunk, not a whole 4 GiB blob.
     *
     * So the number that Telegram actually constrains is the *document*
     * cap, and the single source of truth for it is
     * `Store\ChunkPlanner::PREMIUM_CAP` (the largest document any account
     * can send; 2 GiB for a free account). That cap is validated against in
     * assertChunkSize() below rather than restated here, so the two
     * modules cannot drift apart into a second, divergent constant.
     */
    public const DEFAULT_CHUNK_SIZE = 4 * 1024 * 1024; // 4 MiB — dedup granularity, not a document cap

    /**
     * @param  array<string, mixed>  $setConfig  {paths: list<string>, excludes?: list<string>, chunk_size?: int, set?: string}
     * @return array{uploaded: int, skipped: int, files: int, bytes: int}
     */
    public static function run(array $setConfig, VaultInterface $vault, string $passphrase): array
    {
        $paths = $setConfig['paths'] ?? null;
        if (! is_array($paths) || $paths === []) {
            throw new InvalidArgumentException('setConfig must list at least one path');
        }
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                throw new InvalidArgumentException('setConfig paths must be non-empty strings');
            }
        }

        $excludes = [];
        if (($setConfig['excludes'] ?? null) !== null) {
            if (! is_array($setConfig['excludes'])) {
                throw new InvalidArgumentException('setConfig excludes must be a list of strings');
            }
            $excludes = $setConfig['excludes'];
        }

        $chunkSize = self::DEFAULT_CHUNK_SIZE;
        if (($setConfig['chunk_size'] ?? null) !== null) {
            if (! is_int($setConfig['chunk_size']) || $setConfig['chunk_size'] < 1) {
                throw new InvalidArgumentException('setConfig chunk_size must be a positive integer');
            }
            $chunkSize = self::assertChunkSize($setConfig['chunk_size']);
        }

        $setId = is_string($setConfig['set'] ?? null) ? $setConfig['set'] : '';

        // Per-set lock SHARED with Pruner (chunk GC): a prune running in
        // parallel must never delete chunks of this in-flight backup. Held
        // by another runner → fail loud (two concurrent backups of one set
        // is a scheduling bug); Redis unavailable → degrade to the
        // historical unlocked behavior (warning logged, never a crash).
        $lock = self::acquireSetLock($setId);
        if (! $lock['acquired']) {
            if (! $lock['unavailable']) {
                throw new RuntimeException(sprintf(
                    'backup set "%s" is already locked — another backup is in flight, or a stale lock within %d s',
                    $setId,
                    self::LOCK_TTL_SECONDS,
                ));
            }
            // Redis unavailable: proceed WITHOUT the lock (see acquireSetLock()).
        }

        try {
            $previous = $vault->getLatestManifest();
            $salt = self::saltForSet($previous);
            $key = VaultCrypto::deriveKey($passphrase, $salt);

            $known = self::previousChunkHashes($previous);
            // Ciphertext → plaintext address book, carried forward so a
            // chunk skipped as already-stored keeps its wire name in the new
            // manifest (its ciphertext is not recomputed — and could not be,
            // encryption is non-deterministic).
            $wireChunks = self::previousWireChunks($previous);

            $files = [];
            $chunkHashes = [];
            $uploaded = 0;
            $skipped = 0;
            $bytes = 0;

            foreach (self::collectFiles($paths, $excludes) as $absolute => $relative) {
                $data = file_get_contents($absolute);
                if ($data === false) {
                    throw new RuntimeException("unreadable file: {$absolute}");
                }

                $bytes += strlen($data);
                $fileChunks = [];

                foreach (Chunker::split($data, $chunkSize) as $chunk) {
                    $hash = $chunk['hash'];
                    $fileChunks[] = $hash;
                    $chunkHashes[$hash] = true;

                    if (isset($known[$hash])) {
                        $skipped++;

                        continue;
                    }

                    // Seal first, then name the document by the CIPHERTEXT:
                    // the plaintext address must never be a name on the wire.
                    $cipher = VaultCrypto::encryptChunk($key, $chunk['data']);
                    $wireHash = hash('sha256', $cipher);
                    $vault->putChunk($wireHash, $cipher);
                    $wireChunks[$wireHash] = $hash;
                    $known[$hash] = true;
                    $uploaded++;
                }

                $files[] = ['path' => $relative, 'size' => strlen($data), 'chunks' => $fileChunks];
            }

            $manifest = [
                'version' => self::MANIFEST_VERSION,
                'set' => $setId,
                'generation' => self::nextGeneration($previous),
                'salt' => bin2hex($salt),
                'created' => gmdate('c'),
                'files' => $files,
                'chunk_hashes' => array_keys($chunkHashes),
                'wire_chunks' => $wireChunks,
            ];

            $vault->putManifest(
                (string) json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            );

            return ['uploaded' => $uploaded, 'skipped' => $skipped, 'files' => count($files), 'bytes' => $bytes];
        } finally {
            if ($lock['acquired']) {
                self::releaseSetLock($setId, (string) $lock['token']);
            }
        }
    }

    // ------------------------------------------------------------------
    // Chunk size — bounded by the Telegram document cap, defined once
    // ------------------------------------------------------------------

    /**
     * Reject a chunk_size that no account could ever upload. The cap is read
     * from `Store\ChunkPlanner` — the single source of truth for Telegram's
     * document limits (PART_SIZE 512 KiB, FREE_MAX_PARTS 4000, PREMIUM_MAX_PARTS
     * 8000) — rather than duplicated as a literal here, so a future change to
     * the part math moves both modules at once.
     *
     * PREMIUM_CAP is the ceiling that holds for EVERY account; a free account
     * is bounded lower (FREE_CAP, 2 GiB), but this module has no account
     * context to check that against, so it validates the universal ceiling and
     * leaves the free-account check to the TStore path, which does know
     * whether the account is premium.
     *
     * @return int the validated size
     */
    private static function assertChunkSize(int $size): int
    {
        if ($size > ChunkPlanner::PREMIUM_CAP) {
            throw new InvalidArgumentException(sprintf(
                'setConfig chunk_size %d exceeds the Telegram document cap of %d bytes (4 GiB, 512 KiB parts * %d) — a backup chunk is uploaded as one whole document, so it cannot exceed it',
                $size,
                ChunkPlanner::PREMIUM_CAP,
                ChunkPlanner::PREMIUM_MAX_PARTS,
            ));
        }

        return $size;
    }

    // ------------------------------------------------------------------
    // Per-set backup lock (raw Redis SET NX EX) — shared with Pruner
    // ------------------------------------------------------------------

    /**
     * Atomically acquire the per-set backup lock. The Redis adapter surface
     * has no higher-level lock primitive, so this issues the raw
     * `SET key token NX EX ttl` on the cache store's underlying redis
     * client — the same client seam the relay ticket broker uses
     * (src/Laravel/Services/RelayTicketBroker.php): Cache::store() →
     * RedisStore::connection(), checked for the phpredis/predis driver
     * union. Laravel's own RedisLock uses the identical call shape.
     *
     * Tri-state result:
     * - acquired=true,  token=string  → this caller now holds the lock;
     * - acquired=false, unavailable=false → another holder owns it;
     * - acquired=false, unavailable=true  → no Redis client could be
     *   reached (not configured / non-redis cache / error). A PSR-3
     *   warning is logged when a logger is available, and callers are
     *   expected to PROCEED WITHOUT the lock — a missing lock must never
     *   crash a backup, and the TTL still bounds any crash-wedged holder.
     *
     * The token (256-bit random) makes release safe: only the exact holder
     * can compare-and-delete it.
     *
     * @return array{acquired: bool, unavailable: bool, token: ?string}
     */
    public static function acquireSetLock(string $setId): array
    {
        $connection = self::lockConnection();
        if ($connection === null) {
            self::warn(sprintf(
                'per-set backup lock unavailable (no Redis-backed cache store); running WITHOUT the lock for set "%s"',
                $setId,
            ));

            return ['acquired' => false, 'unavailable' => true, 'token' => null];
        }

        $token = bin2hex(random_bytes(32));

        try {
            $set = $connection->set(
                self::LOCK_KEY_PREFIX.$setId,
                $token,
                'EX',
                self::LOCK_TTL_SECONDS,
                'NX',
            );
            if ($set === true) {
                return ['acquired' => true, 'unavailable' => false, 'token' => $token];
            }
        } catch (Throwable $e) {
            self::warn(sprintf(
                'per-set backup lock acquire failed (%s); running WITHOUT the lock for set "%s"',
                $e->getMessage(),
                $setId,
            ));

            return ['acquired' => false, 'unavailable' => true, 'token' => null];
        }

        return ['acquired' => false, 'unavailable' => false, 'token' => null];
    }

    /**
     * Release the per-set lock held by this caller. Compare-and-delete
     * (Lua GET == token → DEL), so a lock already replaced or expired is
     * never destroyed by a stale release. Release failures are logged and
     * swallowed — the lock TTL is the backstop.
     */
    public static function releaseSetLock(string $setId, string $token): void
    {
        $connection = self::lockConnection();
        if ($connection === null) {
            return; // never acquired (unavailable path) — nothing to release
        }

        try {
            $connection->eval(self::RELEASE_SCRIPT, 1, self::LOCK_KEY_PREFIX.$setId, $token);
        } catch (Throwable $e) {
            self::warn(sprintf(
                'per-set backup lock release failed for set "%s" (%s); the lock TTL (%d s) will clear it',
                $setId,
                $e->getMessage(),
                self::LOCK_TTL_SECONDS,
            ));
        }
    }

    /** Pin the Redis client used for the per-set backup lock (shared with Pruner). */
    public static function setLockConnection(PhpRedisConnection|PredisConnection|null $connection): void
    {
        self::$lockConnection = $connection;
    }

    /** Pin the PSR-3 target for lock degradation warnings; null restores the silent default. */
    public static function setLockLogger(?LoggerInterface $logger): void
    {
        self::$lockLogger = $logger;
    }

    /**
     * Resolve the lock's Redis client: the static seam first; otherwise a
     * lazy Cache::store() → RedisStore::connection() client (the relay
     * ticket broker's acquisition seam), cached after first resolution.
     * null = no Redis client available (callers degrade to unlocked).
     */
    private static function lockConnection(): PhpRedisConnection|PredisConnection|null
    {
        if (self::$lockConnection !== null) {
            return self::$lockConnection;
        }

        try {
            $repository = Cache::store();
            if ($repository instanceof Repository) {
                $store = $repository->getStore();
                if ($store instanceof RedisStore) {
                    $connection = $store->connection();
                    if ($connection instanceof PhpRedisConnection || $connection instanceof PredisConnection) {
                        self::$lockConnection = $connection;

                        return $connection;
                    }
                }
            }
        } catch (Throwable) {
            // Outside Laravel (plain PHP), or no cache configured: degraded.
        }

        return null;
    }

    /**
     * PSR-3 seam for lock degradation warnings: the static pin wins; else
     * the container's LoggerInterface binding when a Laravel app is live
     * (NotifyCommand's pattern). No logger → the engine stays silent.
     */
    private static function lockLogger(): ?LoggerInterface
    {
        if (self::$lockLogger !== null) {
            return self::$lockLogger;
        }

        try {
            $container = Container::getInstance();
            if ($container->bound(LoggerInterface::class)) {
                return $container->make(LoggerInterface::class);
            }
        } catch (Throwable) {
            // no container — stay silent
        }

        return null;
    }

    private static function warn(string $message): void
    {
        $logger = self::lockLogger();
        if ($logger !== null) {
            $logger->warning($message);
        }
    }

    /**
     * Stable per-set salt: reuse the latest manifest's salt when present so
     * the derived key — and therefore every chunk hash — stays identical
     * across runs; otherwise mint a fresh one for this new set.
     *
     * @param  array<string, mixed>|null  $manifest
     */
    private static function saltForSet(?array $manifest): string
    {
        $saltHex = is_array($manifest) ? ($manifest['salt'] ?? null) : null;

        if (is_string($saltHex) && strlen($saltHex) === 32 && ctype_xdigit($saltHex)) {
            return hex2bin($saltHex);
        }

        return VaultCrypto::newSalt();
    }

    /**
     * Next generation: strictly greater than the latest manifest's, so the
     * set only ever moves forward. A manifest with no (or a non-integer)
     * generation is a legacy v1 set and counts as generation 0.
     *
     * @param  array<string, mixed>|null  $manifest
     */
    private static function nextGeneration(?array $manifest): int
    {
        $current = is_array($manifest) ? ($manifest['generation'] ?? null) : null;

        return (is_int($current) && $current > 0 ? $current : 0) + 1;
    }

    /**
     * The previous manifest's ciphertext → plaintext chunk address book,
     * carried into the new manifest. Non-string pairs are dropped rather
     * than trusted (the manifest is data, whatever sealed it).
     *
     * @param  array<string, mixed>|null  $manifest
     * @return array<string, string>
     */
    private static function previousWireChunks(?array $manifest): array
    {
        $wire = is_array($manifest) ? ($manifest['wire_chunks'] ?? null) : null;
        if (! is_array($wire)) {
            return [];
        }

        $carried = [];
        foreach ($wire as $wireHash => $plainHash) {
            if (is_string($wireHash) && is_string($plainHash)) {
                $carried[$wireHash] = $plainHash;
            }
        }

        return $carried;
    }

    /**
     * @param  array<string, mixed>|null  $manifest
     * @return array<string, true>
     */
    private static function previousChunkHashes(?array $manifest): array
    {
        $hashes = is_array($manifest) ? ($manifest['chunk_hashes'] ?? null) : null;
        if (! is_array($hashes)) {
            return [];
        }

        $known = [];
        foreach ($hashes as $hash) {
            if (is_string($hash)) {
                $known[$hash] = true;
            }
        }

        return $known;
    }

    /**
     * Walk every path entry: directories recursively (RecursiveDirectoryIterator,
     * dots skipped, sorted for deterministic manifests), plain files as their
     * basename. Excludes are substring matches against the absolute path.
     *
     * @param  list<string>  $paths
     * @param  list<mixed>  $excludes
     * @return array<string, string> absolute path → manifest-relative path
     */
    private static function collectFiles(array $paths, array $excludes): array
    {
        $collected = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $absolute = $path;
                $relative = basename($path);
                if (! self::isExcluded($absolute, $excludes)) {
                    $collected[$absolute] = $relative;
                }

                continue;
            }

            if (! is_dir($path)) {
                throw new RuntimeException("backup set path not found: {$path}");
            }

            $root = rtrim($path, '/');
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $info) {
                /** @var \SplFileInfo $info */
                if (! $info->isFile()) {
                    continue;
                }
                $absolute = $info->getPathname();
                if (self::isExcluded($absolute, $excludes)) {
                    continue;
                }
                $relative = substr($absolute, strlen($root) + 1);
                $collected[$absolute] = $relative;
            }
        }

        ksort($collected);

        return $collected;
    }

    /**
     * @param  list<mixed>  $excludes
     */
    private static function isExcluded(string $absolute, array $excludes): bool
    {
        foreach ($excludes as $exclude) {
            if (is_string($exclude) && $exclude !== '' && str_contains($absolute, $exclude)) {
                return true;
            }
        }

        return false;
    }
}
