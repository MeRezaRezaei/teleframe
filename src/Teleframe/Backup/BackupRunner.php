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

    /**
     * Crash-safe lock TTL, in seconds: 6 h (raised from 4 h).
     *
     * This is the FLOOR, not the budget for a backup. The budget is
     * carried by the refresh below (LOCK_REFRESH_DIVISOR) — the runner
     * extends the lock from its per-chunk loop, so a backup of any
     * length keeps its lock as long as it keeps making progress. The TTL
     * only has to survive the gap between two refreshes plus whatever
     * stall fits in it, and it is what lets a holder that dies WITHOUT
     * releasing un-wedge the set (Pruner skips a held set; a wedged set
     * is a set nobody can prune for hours, which is precisely why the
     * TTL must stay finite and must never be deleted).
     *
     * Why raised 4 h -> 6 h: the arithmetic that motivated the refresh is
     * `teleframe.backup.chunk_size` = 4 MiB with ONE Telegram message per
     * chunk, so a 4 GiB source is 1024 sequential round-trips — at ~15 s
     * each, ~4.3 h, i.e. ALREADY past the old 4 h TTL. A refresh fixes
     * that on its own, so the raise is deliberately modest: it only has
     * to cover a slower-than-expected per-chunk upload and a scheduling
     * hiccup, not a whole backup. Six hours keeps the crash-wedge window
     * bounded at a number an operator can reason about (an overnight
     * cron will have cleared it by morning) without pretending the TTL
     * is what protects a long backup — that is the refresh's job.
     */
    private const LOCK_TTL_SECONDS = 21600;

    /**
     * How often the runner refreshes its lock, as a DIVISOR of the TTL:
     * every LOCK_TTL_SECONDS / 3 (2 h at the current TTL).
     *
     * A third leaves room for TWO consecutive refreshes to be missed
     * before the lock can actually expire — a refresh that fires at the
     * TTL itself would guarantee a loss, and one that fires at half the
     * TTL would tolerate only a single miss. A third is also frequent
     * enough that the oldest possible expiry after a refresh is 2/3 of
     * the TTL away, so the prune that respects this lock cannot arrive
     * in the gap.
     */
    private const LOCK_REFRESH_DIVISOR = 3;

    /** Compare-and-delete release: only the current holder may drop its own lock. */
    private const RELEASE_SCRIPT = <<<'LUA'
if redis.call('GET', KEYS[1]) == ARGV[1] then
  return redis.call('DEL', KEYS[1])
end
return 0
LUA;

    /**
     * Compare-and-REFRESH, atomic in one round trip: read the token and
     * extend only if it matches, or report 0.
     *
     * This is why the script exists rather than a `GET` followed by an
     * `EXPIRE` from PHP: a read-then-write has a window between the two
     * calls in which the lock expires and is stolen by someone else —
     * and then the stale holder's `EXPIRE` would extend the NEW holder's
     * lock, hiding a lost lock behind a healthy-looking one. Inside Lua
     * the compare and the EXPIRE are one indivisible step.
     *
     * The return value is the whole safety mechanism, and it is tri-state
     * by construction:
     *
     *  - 1 → extended; we still hold the lock;
     *  - 0 → the key does not hold our token: either it expired and was
     *    re-acquired, or it was deleted. The lock is DEMONSTRABLY GONE
     *    and someone else may hold it, so the caller MUST abort;
     *  - anything else is not proof of anything (a transport failure
     *    surfaces as false, not 0) and is handled as a blip.
     */
    private const REFRESH_SCRIPT = <<<'LUA'
if redis.call('GET', KEYS[1]) == ARGV[1] then
  return redis.call('EXPIRE', KEYS[1], ARGV[2])
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
     *  2. This path STREAMS. streamChunks() reads the source in blocks of
     *     at most READ_BLOCK_SIZE and accumulates them until the buffer is
     *     exactly $chunkSize bytes, so the source is never held whole and
     *     the chunk list is never materialised: peak memory is
     *     min($chunkSize, fileSize) + READ_BLOCK_SIZE. (It used to slurp
     *     each file with file_get_contents and hand the whole string to
     *     Chunker::split, which held the file AND every chunk of it at
     *     once — measured peak 2N + 8 MiB for a file of N bytes, i.e. a
     *     file larger than RAM was an OOM. Chunker::split remains for
     *     callers that already hold the bytes; it is not on this path.)
     *
     *     The min() is the load-bearing half. A previous revision read with
     *     `fread($handle, $chunkSize)`, which PRE-ALLOCATES the REQUESTED
     *     length rather than the bytes actually available — so a 5-byte
     *     fixture at the 4 GiB premium cap asked for 4 GiB and killed the
     *     process under a 1 GiB limit. The peak here tracks the SOURCE when
     *     the source is the smaller of the two, so a cap-sized chunk_size
     *     no longer implies a cap-sized allocation.
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
     * Bounded read block for streamChunks(), in BYTES: 1 MiB.
     *
     * A read never requests more than this, however large $chunkSize is, so
     * the ALLOCATION a chunk_size can force is capped at the block rather
     * than at the chunk. The size of this block is a throughput knob only —
     * it never appears on the output, because streamChunks() accumulates
     * whole $chunkSize buffers across however many blocks that takes, so the
     * boundaries (and therefore every content address) are unchanged by it.
     *
     * It must be >= 1: a zero-length read request has its own meaning to
     * fread() and is not a no-op.
     */
    private const READ_BLOCK_SIZE = 1024 * 1024;

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

        // The lock token this runner must present on every refresh, and the
        // time at which the first refresh is due. null token = we hold no
        // lock, so there is nothing to refresh and nothing to lose.
        $lockToken = $lock['acquired'] ? (string) $lock['token'] : null;
        $refreshDueAt = $lockToken === null ? 0 : time() + self::lockRefreshIntervalSeconds();

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

            // A backup that captures no files is NOT a backup. collectFiles()
            // returns [] for an existing-but-empty directory, and for a
            // `paths` list whose every file is swallowed by `excludes` — and
            // it does so WITHOUT throwing, so the run used to walk an empty
            // loop, write a manifest carrying generation = nextGeneration()
            // and created = now, and report SUCCESS. `status` then measured
            // that fresh timestamp against max_age_hours, saw age 0, and
            // stayed green FOREVER: an empty backup is indistinguishable from
            // a working one to every consumer of the vault.
            //
            // The guard lives HERE, not in the CLI, and that is not a
            // style preference. `collectFiles()` is PRIVATE and the count is
            // observable nowhere else: a caller outside this class cannot ask
            // "what did you collect?" without re-walking the filesystem
            // itself, and a second walk would be a second implementation of
            // the exclude semantics that can silently drift from this one.
            // Anything that can call run() — the console command today, and
            // any future scheduler — therefore goes through this check, while
            // a guard bolted onto BackupCommand would protect exactly one
            // caller and be invisible to the next.
            //
            // It must fire BEFORE the manifest write below: a manifest is
            // what `status`, `verify` and `prune` read, so refusing after the
            // write would still advance `generation` and still leave the
            // green timestamp the defect is about.
            $collected = self::collectFiles($paths, $excludes);

            if ($collected === []) {
                throw new RuntimeException(sprintf(
                    'backup set "%s" collected NO FILES: paths=[%s] excludes=[%s]. Those paths exist and were readable, '
                    .'but every file under them was excluded (or each path is an empty directory), so this run would have '
                    .'written a manifest that captures nothing. A backup that captures no files is not a backup, so this '
                    .'refuses rather than reporting success — no manifest was written and `generation` was not advanced.',
                    $setId,
                    implode(', ', $paths),
                    implode(', ', array_map(
                        static fn ($exclude): string => is_string($exclude) ? $exclude : gettype($exclude),
                        $excludes,
                    )),
                ));
            }

            foreach ($collected as $absolute => $relative) {
                // Read STREAMED, one bounded chunk at a time (see
                // streamChunks): the source is never held whole and the
                // chunk list is never materialised, so peak memory is a
                // function of the CHUNK SIZE, not of the file size.
                $fileBytes = 0;
                $fileChunks = [];

                foreach (self::streamChunks($absolute, $chunkSize) as $chunk) {
                    $fileBytes += strlen($chunk['data']);
                    // Lock heartbeat, driven from the PER-CHUNK loop because
                    // that is the loop that knows how far through the upload
                    // this run is. A backup's chunks are content-addressed and
                    // uploaded BEFORE the manifest is written at the very end,
                    // so a chunk in flight is not in the PREVIOUS manifest,
                    // therefore not in Pruner's keep-set, therefore DELETABLE
                    // by a concurrent prune: this lock is the only thing
                    // standing between an in-flight backup and a prune that
                    // destroys it. Without a refresh the lock simply expires
                    // part-way through a large backup and that protection
                    // evaporates silently.
                    if ($lockToken !== null && time() >= $refreshDueAt) {
                        self::refreshLockOrAbort($setId, $lockToken);
                        $refreshDueAt = time() + self::lockRefreshIntervalSeconds();
                    }

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

                $bytes += $fileBytes;
                $files[] = ['path' => $relative, 'size' => $fileBytes, 'chunks' => $fileChunks];
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
            // Also correct when the run aborted on a LOST lock: release is a
            // compare-and-delete on our own token, so a lock that has since
            // been re-acquired by someone else is left strictly alone.
            if ($lock['acquired']) {
                self::releaseSetLock($setId, (string) $lock['token']);
            }
        }
    }

    // ------------------------------------------------------------------
    // Streaming read — the file is never resident
    // ------------------------------------------------------------------

    /**
     * Read $absolute in fixed-size blocks, yielding one chunk at a time.
     *
     * WHY THIS EXISTS. The runner used to do `file_get_contents($path)`
     * and pass the whole string to `Chunker::split()`, which returns a
     * list of every chunk simultaneously. That held the source AND a
     * full second copy of it (the chunk list) at the same instant, so
     * measured peak memory was ~2N for a file of N bytes — linear in the
     * FILE size, unbounded, and a guaranteed OOM (or a silent corrupted
     * backup, depending on which limit bit first) on any file bigger than
     * RAM. No test could see it, because every fixture was kilobytes.
     *
     * Chunking stays BYTE-IDENTICAL to Chunker::split(): blocks are cut
     * at the same $chunkSize boundaries from the same offset 0, so every
     * content address — and therefore the manifest, the dedup index and
     * every wire name — is unchanged. Only the residency changed.
     *
     * A short buffer at EOF (fewer than $chunkSize bytes) is the last chunk
     * and is yielded as-is; a zero-length read that is NOT EOF is a read
     * failure, not an empty file, and is reported as one so a truncated
     * source can never be archived as if it were whole. An empty FILE yields
     * no chunks at all, which is the same contract Chunker::split('', $size)
     * has.
     *
     * WHY THE READ IS BOUNDED, and this is a bug that was shipped and fixed,
     * not a precaution. The read used to be
     *
     *     $block = fread($handle, $chunkSize);
     *
     * which PRE-ALLOCATES the REQUESTED length, not the number of bytes
     * actually available. fread() on a 5-byte source asking for $chunkSize
     * bytes still reserves $chunkSize — so a run at the premium cap
     * (ChunkPlanner::PREMIUM_CAP, 4194304000 bytes) allocated ~4 GiB to read
     * five bytes and died with "Allowed memory size exhausted" under any
     * sane limit. The earlier claim that peak memory was flat across the
     * chunk-size band was measured only between 16 MiB and 256 MiB, where
     * 4x the chunk fits comfortably; it was never measured AT the cap, and
     * the cap is precisely the value the module's own assertChunkSize()
     * accepts.
     *
     * So the read is now capped at READ_BLOCK_SIZE and the accumulator
     * carries bytes across blocks until the buffer is exactly $chunkSize.
     * Peak is min($chunkSize, fileSize) + READ_BLOCK_SIZE: the chunk size
     * still governs the residency of a real chunk, but a chunk_size that
     * exceeds the source no longer costs a chunk_size of memory.
     *
     * The boundaries are what must not move: blocks are cut at the same
     * $chunkSize boundaries from the same offset 0 as Chunker::split(), so
     * every content address — and therefore the manifest, the dedup index
     * and every wire name — is unchanged. READ_BLOCK_SIZE divides nothing;
     * it is invisible on the output.
     *
     * The handle is closed on every exit path, including the generator
     * being abandoned mid-iteration by an exception from the consumer.
     *
     * @return \Generator<int, array{hash: string, data: string}, mixed, void>
     */
    private static function streamChunks(string $absolute, int $chunkSize): \Generator
    {
        $handle = @fopen($absolute, 'rb');
        if ($handle === false) {
            throw new RuntimeException("unreadable file: {$absolute}");
        }

        // Bytes still owed before the buffer is a whole chunk; the buffer is
        // reset to $chunkSize after every yield.
        $pending = $chunkSize;
        $buffer = '';

        try {
            while (! feof($handle)) {
                // Never request more than is useful: the remainder of the
                // pending chunk, capped at the read block.
                $want = $pending < self::READ_BLOCK_SIZE ? $pending : self::READ_BLOCK_SIZE;
                $block = fread($handle, $want);
                if ($block === false) {
                    throw new RuntimeException("unreadable file: {$absolute}");
                }

                if ($block === '') {
                    if (feof($handle)) {
                        break;
                    }

                    // A short read that is not EOF: the source is not
                    // readable to its full length, and archiving the
                    // readable prefix as if it were the whole file would
                    // publish a manifest that silently describes a
                    // truncated source.
                    throw new RuntimeException("unreadable file: {$absolute}");
                }

                $buffer .= $block;
                $pending -= strlen($block);

                if ($pending === 0) {
                    yield ['hash' => hash('sha256', $buffer), 'data' => $buffer];
                    $buffer = '';
                    $pending = $chunkSize;
                }
            }

            // EOF with bytes still owed: the last chunk is SHORT by exactly
            // the same amount Chunker::split() would have made it — a
            // partial tail, never a partial read. Nothing is yielded for an
            // empty remainder, so an empty file yields no chunks.
            if ($buffer !== '') {
                yield ['hash' => hash('sha256', $buffer), 'data' => $buffer];
            }
        } finally {
            fclose($handle);
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

    /**
     * Refresh (extend) the per-set lock this caller still holds.
     *
     * Tri-state result, and the middle value is the one that matters:
     *
     * - refreshed=true                → the token matched; the TTL was extended;
     * - lost=true                     → the script returned 0, meaning the key
     *                                    does NOT hold our token any more. The
     *                                    lock is provably gone and someone else
     *                                    may hold it. THE CALLER MUST ABORT —
     *                                    continuing would turn a lost lock into
     *                                    a silent double-run;
     * - unavailable=true              → we could not find out: no Redis client,
     *                                    or the call raised. NOT proof of a lost
     *                                    lock, so the caller warns and carries
     *                                    on.
     *
     * WHY A BLIP DOES NOT ABORT, because this looks like a bug and is not:
     * chunks are content-addressed, so a run that dies mid-way re-uploads
     * nothing on the next attempt — the worst outcome of aborting on every
     * transient Redis error is a backup that cannot complete while Redis has
     * the occasional hiccup, which trades a WORKING backup for a noisy one.
     * The genuine hazard is a concurrent prune deleting in-flight chunks, and
     * that needs a proven-lost lock (0), not a failed round trip. The TTL is
     * still the backstop: if the blips persist past it, the next refresh
     * returns 0 and the run aborts then.
     *
     * @return array{refreshed: bool, lost: bool, unavailable: bool}
     */
    public static function refreshSetLock(string $setId, string $token): array
    {
        $connection = self::lockConnection();
        if ($connection === null) {
            self::warn(sprintf(
                'per-set backup lock refresh skipped (no Redis-backed cache store) for set "%s"; the lock TTL (%d s) is the backstop',
                $setId,
                self::LOCK_TTL_SECONDS,
            ));

            return ['refreshed' => false, 'lost' => false, 'unavailable' => true];
        }

        try {
            $result = $connection->eval(
                self::REFRESH_SCRIPT,
                1,
                self::LOCK_KEY_PREFIX.$setId,
                $token,
                self::LOCK_TTL_SECONDS,
            );
        } catch (Throwable $e) {
            self::warn(sprintf(
                'per-set backup lock refresh failed for set "%s" (%s); continuing — the lock TTL (%d s) is the backstop',
                $setId,
                $e->getMessage(),
                self::LOCK_TTL_SECONDS,
            ));

            return ['refreshed' => false, 'lost' => false, 'unavailable' => true];
        }

        // ONLY an explicit 0 proves the lock is gone. phpredis surfaces a
        // transport failure as false and a reconnect can surface as null, so
        // those are blips; treating false as "lost" would abort healthy
        // backups on a network hiccup.
        if ($result === 1 || $result === '1' || $result === true) {
            return ['refreshed' => true, 'lost' => false, 'unavailable' => false];
        }

        if ($result === 0 || $result === '0') {
            return ['refreshed' => false, 'lost' => true, 'unavailable' => false];
        }

        self::warn(sprintf(
            'per-set backup lock refresh returned an unexpected value (%s) for set "%s"; treating it as unavailable, not as a lost lock',
            var_export($result, true),
            $setId,
        ));

        return ['refreshed' => false, 'lost' => false, 'unavailable' => true];
    }

    /**
     * The heartbeat's decision, in one place: a proven-lost lock ABORTS the
     * run, anything else lets it continue.
     *
     * @throws RuntimeException when the lock is demonstrably gone
     */
    private static function refreshLockOrAbort(string $setId, string $token): void
    {
        $refresh = self::refreshSetLock($setId, $token);

        if (! $refresh['lost']) {
            return;
        }

        // The lock was released, expired and was re-acquired by someone else
        // while this run was still uploading chunks. Those chunks are orphans
        // by construction (content-addressed, and absent from every published
        // manifest), so a concurrent prune may delete them at any moment —
        // and a second backup of the same set is now running. Continuing would
        // produce a manifest that references chunks another run can delete.
        throw new RuntimeException(sprintf(
            'backup set "%s" LOST its lock mid-run (the lock expired and was re-acquired, or was released by another process): '
            .'aborting rather than continuing unlocked. In-flight chunks are not in any published manifest, so a concurrent '
            .'prune can delete them and a second backup of this set may be running. Re-run the backup; it re-uploads nothing '
            .'already stored. The lock TTL is %d s and is refreshed every %d s while chunks are uploading.',
            $setId,
            self::LOCK_TTL_SECONDS,
            self::lockRefreshIntervalSeconds(),
        ));
    }

    /**
     * Seconds between lock refreshes while chunks are uploading: a third of
     * the TTL (see LOCK_REFRESH_DIVISOR). At least 1 s, so a future TTL
     * smaller than the divisor cannot produce a zero (and therefore
     * every-chunk) refresh interval.
     */
    private static function lockRefreshIntervalSeconds(): int
    {
        return max(1, intdiv(self::LOCK_TTL_SECONDS, self::LOCK_REFRESH_DIVISOR));
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
