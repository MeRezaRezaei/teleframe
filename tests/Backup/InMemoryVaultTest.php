<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Plan P4 Task 2: InMemoryVault semantics — assoc chunk store (latest
 * wins per hash), manifest stack (latest wins), null before the first
 * manifest, loud failure on a missing chunk.
 */
final class InMemoryVaultTest extends TestCase
{
    public function test_chunk_roundtrip_returns_distinct_msg_ids(): void
    {
        $vault = new InMemoryVault();

        $first = $vault->putChunk('hash-a', 'bytes-a');
        $second = $vault->putChunk('hash-b', 'bytes-b');

        self::assertSame('bytes-a', $vault->getChunk('hash-a'));
        self::assertSame('bytes-b', $vault->getChunk('hash-b'));
        self::assertNotSame($first, $second);
        self::assertNotSame('', $first);
    }

    public function test_chunk_reupload_same_hash_latest_wins(): void
    {
        $vault = new InMemoryVault();

        $vault->putChunk('hash-a', 'old-bytes');
        $vault->putChunk('hash-a', 'new-bytes');

        self::assertSame('new-bytes', $vault->getChunk('hash-a'));
    }

    public function test_manifest_latest_wins(): void
    {
        $vault = new InMemoryVault();

        $vault->putManifest('{"version":1}');
        $vault->putManifest('{"version":2}');

        self::assertSame(['version' => 2], $vault->getLatestManifest());
    }

    public function test_latest_manifest_null_before_any_put(): void
    {
        $vault = new InMemoryVault();

        self::assertNull($vault->getLatestManifest());
    }

    public function test_missing_chunk_throws(): void
    {
        $vault = new InMemoryVault();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hash-missing');

        $vault->getChunk('hash-missing');
    }

    public function test_find_messages_by_name_empty_prefix_lists_chunks_and_manifests(): void
    {
        $vault = new InMemoryVault();
        $vault->putChunk('hash-a', 'bytes-a');
        $vault->putManifest('{"version":1}');
        $vault->putChunk('hash-b', 'bytes-b');

        $entries = $vault->findMessagesByName('');

        self::assertSame(['hash-a', 'TBMANIFEST1:{"version":1}', 'hash-b'], array_column($entries, 'name'));
        self::assertSame(['hash-a'], array_column($vault->findMessagesByName('hash-a'), 'name'));
    }

    public function test_delete_removes_only_the_named_entries(): void
    {
        $vault = new InMemoryVault();
        $vault->putChunk('hash-a', 'bytes-a');
        $vault->putChunk('hash-b', 'bytes-b');
        $vault->putManifest('{"version":1}');

        $vault->delete('hash-a');

        self::assertSame('bytes-b', $vault->getChunk('hash-b'), 'other chunks survive the delete');
        self::assertSame(['version' => 1], $vault->getLatestManifest(), 'manifests survive the delete');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hash-a');

        $vault->getChunk('hash-a');
    }
}
