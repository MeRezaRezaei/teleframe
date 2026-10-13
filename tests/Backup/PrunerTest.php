<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use Illuminate\Redis\Connections\PhpRedisConnection;
use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\Pruner;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Night W1-3 (P4-carried M): Pruner — chunk GC against the latest
 * manifest's keep-set. A vault entry dies only when BOTH hold: its name
 * is chunk-shaped (exactly 64 lowercase hex chars — sha256 content
 * addresses) AND it is not in the keep-set. Manifest messages and any
 * non-chunk-shaped entry survive untouched; a second prune is a no-op.
 */
final class PrunerTest extends TestCase
{
    private const KEEP_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const KEEP_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private const ORPHAN = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    /** The overlapping-chunk retention fixture below: four chunk addresses. */
    private const SHARED_A = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';

    private const SHARED_B = 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';

    private const SHARED_C = 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';

    private const SHARED_D = '1111111111111111111111111111111111111111111111111111111111111111';

    protected function tearDown(): void
    {
        // Unpin the static lock seams so no lock state leaks across tests.
        BackupRunner::setLockConnection(null);
        BackupRunner::setLockLogger(null);

        parent::tearDown();
    }

    public function test_prunes_orphaned_chunks_but_never_keep_set_or_manifest(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk(self::KEEP_B, 'b-bytes');
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1,"chunk_hashes":["'.self::KEEP_A.'","'.self::KEEP_B.'"]}');
        $manifestBefore = $vault->getLatestManifest();

        $stats = Pruner::prune($vault, [self::KEEP_A, self::KEEP_B]);

        self::assertSame(['scanned' => 4, 'pruned' => 1], $stats);
        self::assertSame('a-bytes', $vault->getChunk(self::KEEP_A), 'kept chunk must survive');
        self::assertSame('b-bytes', $vault->getChunk(self::KEEP_B), 'kept chunk must survive');
        self::assertSame($manifestBefore, $vault->getLatestManifest(), 'manifest message must survive');

        $this->expectException(RuntimeException::class);
        $vault->getChunk(self::ORPHAN);
    }

    public function test_second_prune_is_a_noop(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');

        Pruner::prune($vault, [self::KEEP_A]);
        $stats = Pruner::prune($vault, [self::KEEP_A]);

        self::assertSame(['scanned' => 1, 'pruned' => 0], $stats);
        self::assertSame('a-bytes', $vault->getChunk(self::KEEP_A));
    }

    public function test_non_chunk_shaped_names_are_never_touched(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk('deadbeef', 'short-hex-name');        // hex but not 64
        $vault->putChunk('notes.txt', 'human file name');      // not hex at all
        $vault->putChunk(strtoupper(self::ORPHAN), 'upper-hex'); // 64 chars, not lowercase hex
        $vault->putManifest('{"version":1}');

        $stats = Pruner::prune($vault, [self::KEEP_A]);

        self::assertSame(['scanned' => 5, 'pruned' => 0], $stats);
        self::assertSame('short-hex-name', $vault->getChunk('deadbeef'));
        self::assertSame('human file name', $vault->getChunk('notes.txt'));
        self::assertSame('upper-hex', $vault->getChunk(strtoupper(self::ORPHAN)));
    }

    public function test_empty_keep_set_prunes_every_chunk(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1}');

        $stats = Pruner::prune($vault, []);

        self::assertSame(['scanned' => 2, 'pruned' => 1], $stats);
    }

    public function test_prune_skips_entirely_when_a_backup_holds_the_set_lock(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1,"set":"docs","chunk_hashes":["'.self::KEEP_A.'"]}');

        $redis = new LockRedisConnection;
        BackupRunner::setLockConnection($redis);

        // In-flight backup: the runner holds the per-set lock. The prune
        // derives the same set id ("docs") from the latest manifest.
        $lock = BackupRunner::acquireSetLock('docs');
        self::assertTrue($lock['acquired']);

        $stats = Pruner::prune($vault, [self::KEEP_A]);

        self::assertSame(
            ['scanned' => 0, 'pruned' => 0],
            $stats,
            'a prune must skip an in-flight backup\'s set entirely',
        );
        self::assertSame('a-bytes', $vault->getChunk(self::KEEP_A));
        self::assertSame('orphan-bytes', $vault->getChunk(self::ORPHAN), 'nothing may be deleted while locked');

        BackupRunner::releaseSetLock('docs', (string) $lock['token']);
        self::assertNull($redis->lockValue('tg:backup:lock:docs'));
    }

    public function test_prune_acquires_and_releases_the_manifest_derived_set_lock(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1,"set":"docs","chunk_hashes":["'.self::KEEP_A.'"]}');

        $redis = new LockRedisConnection;
        BackupRunner::setLockConnection($redis);

        $stats = Pruner::prune($vault, [self::KEEP_A]);

        self::assertSame(['scanned' => 3, 'pruned' => 1], $stats);
        self::assertNull($redis->lockValue('tg:backup:lock:docs'), 'lock must be released after the prune');

        $setCalls = array_values(array_filter(
            $redis->calls(),
            static fn (array $call): bool => $call[0] === 'set',
        ));
        self::assertCount(1, $setCalls);
        self::assertSame('tg:backup:lock:docs', $setCalls[0][1][0], 'set id comes from the latest manifest');

        $evalCalls = array_values(array_filter(
            $redis->calls(),
            static fn (array $call): bool => $call[0] === 'eval',
        ));
        self::assertCount(1, $evalCalls);
        self::assertSame('tg:backup:lock:docs', $evalCalls[0][1][1][0], 'release targets the same key');
    }

    public function test_explicit_set_id_controls_the_lock_key(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1,"chunk_hashes":[]}'); // no "set" field

        $redis = new LockRedisConnection;
        BackupRunner::setLockConnection($redis);

        $stats = Pruner::prune($vault, [], 'archive');

        self::assertSame(['scanned' => 2, 'pruned' => 1], $stats);
        self::assertNull($redis->lockValue('tg:backup:lock:archive'), 'explicit set id lock released');
        self::assertNull($redis->lockValue('tg:backup:lock:'), 'fallback empty-set key must not be used');
    }

    // ── DEFECT-2: the prune must refresh the lock it holds ───────────────

    public function test_a_prune_refreshes_the_per_set_lock_it_holds(): void
    {
        // The prune takes the SAME lock the runner holds for a whole backup,
        // and that lock is the only thing keeping a concurrent backup's
        // not-yet-published chunks — which are in no manifest, so in no
        // keep-set — from being deleted below. A walk that outruns the lock's
        // TTL loses that protection SILENTLY, mid-deletion, so the prune must
        // refresh from inside the loop the way BackupRunner does.
        $vault = new SeedableVault;
        $vault->putManifest('{"version":1,"set":"docs","chunk_hashes":[]}');

        // More entries than the entry-count trigger (100) needs, so the
        // refresh fires without waiting two hours.
        for ($i = 0; $i < 130; $i++) {
            $vault->putChunk(hash('sha256', 'chunk-'.$i), 'bytes-'.$i);
        }

        $redis = new RefreshAwareLockConnection;
        BackupRunner::setLockConnection($redis);

        Pruner::prune($vault, []);

        $refreshes = $redis->refreshKeys();
        self::assertNotEmpty(
            $refreshes,
            'the prune held the per-set lock across a 131-entry walk and never refreshed it: '
            .'a walk longer than the lock TTL would expire mid-prune and the protection would evaporate silently',
        );

        foreach ($refreshes as $key) {
            self::assertSame('tg:backup:lock:docs', $key, 'a refresh must extend the lock the prune actually holds');
        }
    }

    public function test_a_short_prune_still_takes_and_releases_the_lock(): void
    {
        // The refresh is an ADDITION, not a replacement: a prune under the
        // trigger still acquires and releases exactly as before, and does not
        // refresh needlessly.
        $vault = new SeedableVault;
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1,"set":"docs","chunk_hashes":["'.self::KEEP_A.'"]}');

        $redis = new RefreshAwareLockConnection;
        BackupRunner::setLockConnection($redis);

        $stats = Pruner::prune($vault, [self::KEEP_A]);

        self::assertSame(['scanned' => 3, 'pruned' => 1], $stats);
        self::assertSame([], $redis->refreshKeys(), 'a two-entry walk is not due a refresh');
        self::assertNull($redis->lockValue('tg:backup:lock:docs'));
    }

    public function test_a_prune_aborts_when_it_loses_the_lock_mid_walk(): void
    {
        // A lost lock means someone else may now hold the set, so the
        // protection this prune was performing no longer exists. Continuing
        // would delete chunks a running backup is about to reference — so it
        // aborts instead, and reports it loudly rather than through the PSR-3
        // seam the engine's NullLogger default swallows.
        $vault = new SeedableVault;
        $vault->putManifest('{"version":1,"set":"docs","chunk_hashes":[]}');

        for ($i = 0; $i < 130; $i++) {
            $vault->putChunk(hash('sha256', 'chunk-'.$i), 'bytes-'.$i);
        }

        $redis = new RefreshAwareLockConnection;
        $redis->loseLockOnRefresh();
        BackupRunner::setLockConnection($redis);

        try {
            Pruner::prune($vault, []);
            self::fail('a prune that lost the lock must abort rather than continue unlocked');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('LOST its lock during prune', $e->getMessage());
            self::assertStringContainsString('docs', $e->getMessage());
        }
    }

    // ── retention: the keep-set is a UNION, not the newest manifest ──────

    /**
     * Three generations over an OVERLAPPING chunk set, so the union is a real
     * union and a shared chunk has to survive being owned by two manifests:
     *
     *   generation 3 (newest) → C, D
     *   generation 2         → B, C
     *   generation 1 (oldest)→ A, B
     *
     * A is reachable from NO retained generation once the window is 2, and B
     * and C are each reachable from two — a keep-set built per generation
     * and unioned wrongly drops one of them, and a keep-set built from the
     * newest row alone drops B.
     */
    private function overlappingGenerations(): InMemoryVault
    {
        $vault = new InMemoryVault;
        foreach ([self::SHARED_A, self::SHARED_B, self::SHARED_C, self::SHARED_D] as $hash) {
            $vault->putChunk($hash, 'bytes-'.$hash);
        }

        $vault->putManifest(self::manifest(3, [self::SHARED_C, self::SHARED_D]));
        $vault->putManifest(self::manifest(2, [self::SHARED_B, self::SHARED_C]));
        $vault->putManifest(self::manifest(1, [self::SHARED_A, self::SHARED_B]));

        return $vault;
    }

    /** A manifest in BackupRunner's shape, addressed by PLAINTEXT name (no wire book). */
    private static function manifest(int $generation, array $hashes): string
    {
        return (string) json_encode([
            'version' => 1,
            'set' => 'docs',
            'generation' => $generation,
            'created' => gmdate('c'),
            'files' => [],
            'chunk_hashes' => $hashes,
        ]);
    }

    public function test_the_keep_set_unions_every_retained_generation_not_just_the_newest(): void
    {
        $vault = $this->overlappingGenerations();

        $keep = Pruner::retentionKeepHashes($vault, [], 2);

        self::assertEqualsCanonicalizing(
            [self::SHARED_B, self::SHARED_C, self::SHARED_D],
            $keep,
            'the keep-set is the UNION of generations 3 and 2: C and D from the newest, B from the one behind it, A dropped',
        );
        self::assertCount(3, $keep, 'a chunk owned by two generations is named ONCE, not twice');
    }

    public function test_the_window_width_moves_the_boundary(): void
    {
        $vault = $this->overlappingGenerations();

        $all = Pruner::retentionKeepHashes($vault, [], 3);
        self::assertEqualsCanonicalizing(
            [self::SHARED_A, self::SHARED_B, self::SHARED_C, self::SHARED_D],
            $all,
            'a three-generation window reaches the oldest manifest, so nothing is collectable',
        );

        $one = Pruner::retentionKeepHashes($vault, [], 1);
        self::assertEqualsCanonicalizing(
            [self::SHARED_C, self::SHARED_D],
            $one,
            'one generation is the newest row alone — which is exactly the pre-retention behaviour',
        );
    }

    public function test_the_keep_set_is_never_narrower_than_what_the_caller_already_held(): void
    {
        $vault = new SeedableVault;
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putManifest(self::manifest(1, [self::KEEP_A]));

        // The caller's own latest-manifest hashes are unioned in even though
        // the vault's retention listing answers nothing: a keep-set can only
        // ever grow here, never shrink below what the caller already held.
        $keep = Pruner::retentionKeepHashes($vault, [self::KEEP_A, self::ORPHAN], 1);

        self::assertSame([self::KEEP_A, self::ORPHAN], $keep);
    }

    public function test_a_retention_prune_keeps_every_retained_generation_restorable(): void
    {
        $vault = $this->overlappingGenerations();

        $keep = Pruner::retentionKeepHashes($vault, [], 2);
        $stats = Pruner::prune($vault, $keep);

        self::assertSame(['scanned' => 7, 'pruned' => 1], $stats, '3 manifests + 4 chunks walked; only A is collectable');

        $restorable = [];
        foreach ($vault->listManifests(10) as $row) {
            $restorable[$row['generation']] = $row['restorable'];
        }

        self::assertSame(
            [3 => true, 2 => true, 1 => false],
            $restorable,
            'the two retained generations survive intact and generation 1 — which A belonged to — cannot be restored',
        );
    }

    public function test_a_narrower_window_prunes_more_and_destroys_more(): void
    {
        $vault = $this->overlappingGenerations();

        $keep = Pruner::retentionKeepHashes($vault, [], 1);
        $stats = Pruner::prune($vault, $keep);

        self::assertSame(['scanned' => 7, 'pruned' => 2], $stats, 'only C and D stay; A and B are collectable');

        $restorable = [];
        foreach ($vault->listManifests(10) as $row) {
            $restorable[$row['generation']] = $row['restorable'];
        }

        self::assertSame(
            [3 => true, 2 => false, 1 => false],
            $restorable,
            'narrowing the window costs generation 2 its restorability as well',
        );
    }
}

/**
 * Pruner-test fake: a flat message list behind VaultInterface. Entries
 * remember their name so findMessagesByName('') lists everything and
 * delete() only removes exact-name matches — mirroring what a channel
 * vault does, without a network.
 */
final class SeedableVault implements VaultInterface
{
    use RetentionStubTrait;

    /** @var list<array{id: int, name: string, is_manifest: bool}> oldest first */
    private array $messages = [];

    /** @var array<string, string> name → bytes */
    private array $documents = [];

    private int $nextMsgId = 1;

    public function putChunk(string $hash, string $bytes): string
    {
        $this->messages[] = ['id' => $this->nextMsgId, 'name' => $hash, 'is_manifest' => false];
        $this->documents[$hash] = $bytes;

        return (string) $this->nextMsgId++;
    }

    public function getChunk(string $hash): string
    {
        if (! array_key_exists($hash, $this->documents)) {
            throw new RuntimeException("chunk {$hash} not stored in this vault");
        }

        return $this->documents[$hash];
    }

    public function putManifest(string $json): string
    {
        $this->messages[] = ['id' => $this->nextMsgId, 'name' => 'TBMANIFEST1:'.$json, 'is_manifest' => true];

        return (string) $this->nextMsgId++;
    }

    public function getLatestManifest(): ?array
    {
        $latest = null;
        foreach ($this->messages as $message) {
            if ($message['is_manifest']) {
                $latest = $message;
            }
        }
        if ($latest === null) {
            return null;
        }

        $decoded = json_decode(substr($latest['name'], strlen('TBMANIFEST1:')), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('latest manifest is not valid JSON');
        }

        return $decoded;
    }

    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
    {
        $unreadableIds = [];
        $read = [];
        foreach ($this->messages as $message) {
            if (! $message['is_manifest']) {
                continue;
            }
            $decoded = json_decode(substr($message['name'], strlen('TBMANIFEST1:')), true);
            if (! is_array($decoded)) {
                $unreadableIds[] = $message['id'];

                continue;
            }
            $read[] = $decoded;
        }
        usort($read, static fn (array $a, array $b): int => ($b['generation'] ?? 0) <=> ($a['generation'] ?? 0));

        $rows = [];
        foreach ($read as $manifest) {
            $rows[] = [
                'generation' => (int) ($manifest['generation'] ?? 0),
                'created' => is_string($manifest['created'] ?? null) ? $manifest['created'] : 'unknown',
                'files' => count(is_array($manifest['files'] ?? null) ? $manifest['files'] : []),
                'chunks' => count(is_array($manifest['chunk_hashes'] ?? null) ? $manifest['chunk_hashes'] : []),
                'bytes' => 0,
                'restorable' => true,
                'missing_chunks' => 0,
            ];
            if (count($rows) >= max(1, $limit)) {
                break;
            }
        }

        return $rows;
    }

    public function findMessagesByName(string $namePrefix): array
    {
        $entries = [];
        foreach ($this->messages as $message) {
            if (str_starts_with($message['name'], $namePrefix)) {
                $entries[] = ['id' => (string) $message['id'], 'name' => $message['name']];
            }
        }

        return $entries;
    }

    public function delete(string $name): void
    {
        $this->messages = array_values(array_filter(
            $this->messages,
            static fn (array $message): bool => $message['name'] !== $name,
        ));
        unset($this->documents[$name]);
    }
}

/**
 * A phpredis connection fake that tells the two lock Lua scripts apart.
 *
 * The existing LockRedisConnection answers EVERY eval with its compare-and-
 * delete semantics, which is exactly right for a release and exactly wrong
 * for a REFRESH: the refresh script returns 1 when the token still matches
 * and 0 when it does not, but the existing fake's handleEval() also returns
 * 1 whenever the token matches — so a refresh issued under a held lock would
 * pass, and a test could not tell a refresh from a release, nor simulate the
 * lock being lost mid-walk.
 *
 * The two are distinguishable on the wire: the refresh script carries
 * 'EXPIRE' and takes the TTL as a trailing ARGV, the release script carries
 * 'DEL' and takes no TTL. This fake dispatches on the script body and records
 * which keys were refreshed, and can be told to answer every refresh with 0 —
 * which is exactly what Redis does when the lock expired and someone else
 * took it.
 */
final class RefreshAwareLockConnection extends PhpRedisConnection
{
    /** @var array<string, string> key → current value */
    private array $store = [];

    /** @var list<string> keys passed to the REFRESH script, in call order */
    private array $refreshKeys = [];

    /** answer every refresh with 0 — the key no longer holds our token */
    private bool $lockLostOnRefresh = false;

    public function __construct()
    {
        parent::__construct(new \stdClass);
    }

    /** Make every refresh report the lock as gone (script returns 0). */
    public function loseLockOnRefresh(): void
    {
        $this->lockLostOnRefresh = true;
    }

    /**
     * The keys the prune refreshed, in call order.
     *
     * @return list<string>
     */
    public function refreshKeys(): array
    {
        return $this->refreshKeys;
    }

    public function lockValue(string $key): ?string
    {
        return $this->store[$key] ?? null;
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function command($method, array $parameters = [])
    {
        $method = strtolower((string) $method);

        return match ($method) {
            'set' => $this->handleSet($parameters),
            'get' => $this->store[(string) ($parameters[0] ?? '')] ?? false,
            'del' => $this->handleDel($parameters),
            'eval' => $this->handleEval($parameters),
            default => false,
        };
    }

    /**
     * @param  array<mixed>  $parameters
     */
    private function handleSet(array $parameters): bool
    {
        $key = (string) ($parameters[0] ?? '');
        $value = (string) ($parameters[1] ?? '');
        $options = is_array($parameters[2] ?? null) ? $parameters[2] : [];

        if (in_array('NX', $options, true) && array_key_exists($key, $this->store)) {
            return false;
        }

        $this->store[$key] = $value;

        return true;
    }

    /**
     * @param  array<mixed>  $parameters
     */
    private function handleDel(array $parameters): int
    {
        $keys = is_array($parameters[0] ?? null) ? $parameters[0] : [$parameters[0] ?? null];
        $removed = 0;
        foreach ($keys as $key) {
            if (isset($this->store[(string) $key])) {
                unset($this->store[(string) $key]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Dispatch on the script body: REFRESH (EXPIRE, TTL in ARGV) vs RELEASE
     * (DEL, no TTL). Wire shape forwarded by PhpRedisConnection::eval is
     * [$script, $arguments, $numKeys] with the first $numKeys arguments being
     * the KEYS and the rest ARGV — the same shape LockRedisConnection reads.
     *
     * @param  array<mixed>  $parameters
     */
    private function handleEval(array $parameters): int
    {
        $script = (string) ($parameters[0] ?? '');
        $arguments = is_array($parameters[1] ?? null) ? $parameters[1] : [];
        $numKeys = (int) ($parameters[2] ?? 0);

        $key = (string) (array_slice($arguments, 0, $numKeys)[0] ?? '');
        $token = (string) (array_slice($arguments, $numKeys)[0] ?? '');

        if (str_contains($script, 'EXPIRE')) {
            $this->refreshKeys[] = $key;

            if ($this->lockLostOnRefresh) {
                return 0; // our token is no longer the key's value
            }

            return ($this->store[$key] ?? null) === $token && $token !== '' ? 1 : 0;
        }

        // RELEASE
        if (($this->store[$key] ?? null) === $token && $token !== '') {
            unset($this->store[$key]);

            return 1;
        }

        return 0;
    }
}
