<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\Pruner;
use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Reviewer-verdict regression pin (night quality): findEntries capped at
 * SEARCH_LIMIT=100, so orphans older than the newest ~100 channel
 * messages were invisible to the prune GC — at ≥50 MB backup sets the
 * walk stopped before the orphans and they survived forever. The fix:
 * TelegramVault::listAllEntries() — an UNcapped messages.getHistory
 * offset_id walk (pages of 100 until an empty page) that Pruner rides
 * via method_exists duck-typing, with a 10000-entry hard cap that
 * THROWS for manual review instead of silently truncating.
 *
 * The harness is a scope double under the REAL scopeApi map (forScope):
 * getHistory pages 100+100+50 from an in-memory store, searchMessages
 * mirrors server-side search over all history (delete() rides it),
 * getDialogs resolves the channel, deleteMessages revokes for real.
 */
final class PrunePaginationTest extends TestCase
{
    private const KEEP_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa0001';

    private const KEEP_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb0002';

    public function test_prune_scans_all_250_messages_and_prunes_orphans_beyond_the_old_100_cap(): void
    {
        $scope = new PaginatedHistoryScope('set250');
        $scope->seedManifest(250, '{"version":1,"chunk_hashes":["' . self::KEEP_A . '","' . self::KEEP_B . '"]}');
        $scope->seedChunk(249, self::KEEP_A);
        $scope->seedChunk(248, self::KEEP_B);
        foreach (range(1, 247) as $id) {
            $scope->seedChunk($id, self::orphanHash($id));
        }
        $vault = TelegramVault::forScope($scope, 'set250');

        $stats = Pruner::prune($vault, [self::KEEP_A, self::KEEP_B]);

        self::assertSame(250, $stats['scanned'], 'the prune walk must see EVERY entry, not just the newest 100');
        self::assertSame(247, $stats['pruned']);
        self::assertTrue($scope->wasDeleted(100), 'orphan beyond position 100 (the hidden-by-cap class) IS pruned — the regression pin');
        self::assertTrue($scope->wasDeleted(1), 'the OLDEST orphan is pruned too');
        foreach ([248, 249, 250] as $kept) {
            self::assertFalse($scope->wasDeleted($kept), "keep-set chunk / manifest message {$kept} survives");
        }
    }

    public function test_the_walk_pages_history_100_100_50_then_an_empty_page(): void
    {
        $scope = new PaginatedHistoryScope('set250');
        $scope->seedManifest(250, '{"version":1}');
        foreach (range(1, 249) as $id) {
            $scope->seedChunk($id, self::orphanHash($id));
        }
        $vault = TelegramVault::forScope($scope, 'set250');

        $entries = $vault->listAllEntries();

        self::assertCount(250, $entries);
        self::assertSame(
            [
                ['limit' => 100, 'offset_id' => 0],
                ['limit' => 100, 'offset_id' => 151], // oldest id of page 1 (250..151)
                ['limit' => 100, 'offset_id' => 51],  // oldest id of page 2 (150..51)
                ['limit' => 100, 'offset_id' => 1],   // oldest id of page 3 (50..1)
            ],
            $scope->historyCalls,
            'pagination terminates on the empty page: exactly 100+100+50 rows plus the empty probe',
        );
    }

    public function test_list_all_entries_matches_find_messages_by_name_shape(): void
    {
        $scope = new PaginatedHistoryScope('shape');
        $scope->seedChunk(2, self::KEEP_A);
        $scope->seedManifest(1, '{"version":1}');

        $vault = TelegramVault::forScope($scope, 'shape');
        $entries = $vault->listAllEntries();

        self::assertCount(2, $entries);
        foreach ($entries as $entry) {
            self::assertSame(['id', 'name'], array_keys($entry));
            self::assertIsString($entry['id']);
            self::assertIsString($entry['name']);
        }
        self::assertSame(self::KEEP_A, $entries[0]['name'], 'newest first, like findMessagesByName');
    }

    public function test_hard_cap_throws_a_clear_runtime_exception(): void
    {
        $scope = new PaginatedHistoryScope('cap');
        foreach (range(1, 10001) as $id) {
            $scope->seedChunk($id, self::orphanHash($id));
        }
        $vault = TelegramVault::forScope($scope, 'cap');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('manual review');

        $vault->listAllEntries();
    }

    private static function orphanHash(int $id): string
    {
        return str_pad(strtolower(base_convert((string) $id, 10, 16)), 64, 'c', STR_PAD_LEFT);
    }
}

/**
 * Scope double under the real scopeApi map: messages.getHistory pages
 * newest-first from an in-memory store honoring offset_id (ids strictly
 * below the cursor), searchMessages mirrors server-side search over ALL
 * history (delete() of old orphans rides it), getDialogs resolves the
 * vault channel, deleteMessages actually revokes. No network.
 */
class PaginatedHistoryScope extends UserAccountScope
{
    private const CHANNEL_ID = 555;

    private const CHANNEL_ACCESS_HASH = 990;

    /** @var list<array<string, mixed>> oldest-first MTProto-shaped message rows */
    private array $messages = [];

    /** @var list<array{limit: int, offset_id: int}> */
    public array $historyCalls = [];

    /** @var list<int> */
    private array $deletedIds = [];

    public function __construct(private readonly string $setId)
    {
        parent::__construct(
            new MTProtoClient(apiId: 1, apiHash: 'x', session: new SessionData(dcId: 2, authKey: 'k', userId: 42)),
            new SessionData(dcId: 2, authKey: 'k', userId: 42),
        );
    }

    public function seedChunk(int $id, string $hash): void
    {
        $this->messages[] = [
            'id' => $id,
            'media' => ['document' => ['_' => 'document', 'id' => $id, 'access_hash' => 1, 'file_reference' => '', 'attributes' => [
                ['_' => 'documentAttributeFilename', 'file_name' => $hash],
            ]]],
        ];
    }

    public function seedManifest(int $id, string $json): void
    {
        $this->messages[] = ['id' => $id, 'message' => 'TBMANIFEST1:' . base64_encode($json)];
    }

    public function wasDeleted(int $id): bool
    {
        return in_array($id, $this->deletedIds, true);
    }

    public function getHistory(int|string|array $peer, int $limit = 50, int $offsetId = 0): array
    {
        $this->historyCalls[] = ['limit' => $limit, 'offset_id' => $offsetId];

        $page = [];
        foreach ($this->newestFirst() as $row) {
            if ($offsetId !== 0 && (int) $row['id'] >= $offsetId) {
                continue; // offset_id cursor: only strictly-older rows
            }
            $page[] = $row;
            if (count($page) >= $limit) {
                break;
            }
        }

        return ['_' => 'messages.messagesSlice', 'messages' => $page, 'chats' => [], 'users' => [], 'count' => count($this->messages)];
    }

    public function searchMessages(int|string|array $peer, string $query, int $limit = 50, array $options = []): array
    {
        $rows = [];
        foreach ($this->newestFirst() as $row) {
            if (str_contains($this->rowName($row) ?? '', $query)) {
                $rows[] = $row;
                if (count($rows) >= $limit) {
                    break;
                }
            }
        }

        return ['_' => 'messages.messagesSlice', 'messages' => $rows, 'chats' => [], 'users' => [], 'count' => count($rows)];
    }

    public function getDialogs(int $limit = 50, int $offsetDate = 0): array
    {
        return ['_' => 'messages.dialogs', 'dialogs' => [], 'chats' => [
            ['_' => 'channel', 'id' => self::CHANNEL_ID, 'access_hash' => self::CHANNEL_ACCESS_HASH, 'title' => TelegramVault::channelTitle($this->setId)],
        ], 'messages' => [], 'users' => []];
    }

    public function deleteMessages(array $messageIds, bool $revoke = true): array
    {
        foreach ($messageIds as $id) {
            $this->deletedIds[] = (int) $id;
        }
        $this->messages = array_values(array_filter(
            $this->messages,
            static fn (array $row): bool => !in_array((int) $row['id'], $messageIds, true),
        ));

        return ['_' => 'updates.affectedMessages', 'pts' => 1, 'pts_count' => count($messageIds)];
    }

    /** Seed order is irrelevant: history/search present rows by message id DESC. @return list<array<string, mixed>> */
    private function newestFirst(): array
    {
        $rows = $this->messages;
        usort($rows, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return $rows;
    }

    private function rowName(array $row): ?string
    {
        $attributes = $row['media']['document']['attributes'] ?? null;
        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                if (($attribute['_'] ?? '') === 'documentAttributeFilename' && is_string($attribute['file_name'] ?? null)) {
                    return $attribute['file_name'];
                }
            }
        }

        return is_string($row['message'] ?? null) ? $row['message'] : null;
    }
}
