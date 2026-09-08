<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

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

    public function test_prunes_orphaned_chunks_but_never_keep_set_or_manifest(): void
    {
        $vault = new SeedableVault();
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk(self::KEEP_B, 'b-bytes');
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1,"chunk_hashes":["' . self::KEEP_A . '","' . self::KEEP_B . '"]}');
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
        $vault = new SeedableVault();
        $vault->putChunk(self::KEEP_A, 'a-bytes');
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');

        Pruner::prune($vault, [self::KEEP_A]);
        $stats = Pruner::prune($vault, [self::KEEP_A]);

        self::assertSame(['scanned' => 1, 'pruned' => 0], $stats);
        self::assertSame('a-bytes', $vault->getChunk(self::KEEP_A));
    }

    public function test_non_chunk_shaped_names_are_never_touched(): void
    {
        $vault = new SeedableVault();
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
        $vault = new SeedableVault();
        $vault->putChunk(self::ORPHAN, 'orphan-bytes');
        $vault->putManifest('{"version":1}');

        $stats = Pruner::prune($vault, []);

        self::assertSame(['scanned' => 2, 'pruned' => 1], $stats);
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
        if (!array_key_exists($hash, $this->documents)) {
            throw new RuntimeException("chunk {$hash} not stored in this vault");
        }

        return $this->documents[$hash];
    }

    public function putManifest(string $json): string
    {
        $this->messages[] = ['id' => $this->nextMsgId, 'name' => 'TBMANIFEST1:' . $json, 'is_manifest' => true];

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
        if (!is_array($decoded)) {
            throw new RuntimeException('latest manifest is not valid JSON');
        }

        return $decoded;
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
