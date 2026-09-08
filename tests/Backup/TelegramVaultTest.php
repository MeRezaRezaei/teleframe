<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use MeRezaRezaei\Teleframe\Tests\Support\FakeVaultApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Plan P4 Task 2: TelegramVault offline against the fake api call map —
 * lazy channel resolution (find-else-create, cached), chunk roundtrip via
 * upload+sendDocument with name/caption = hash, manifest posts as
 * 'TBMANIFEST1:'-prefixed text and latest wins, and prefix detection via
 * string functions only (the fake's findMessagesByName matches loosely,
 * so only vault-side str_starts_with/=== can reject decoys).
 */
final class TelegramVaultTest extends TestCase
{
    /** @return array{call: string, args: list<mixed>} the last recorded $name call */
    private function callOf(FakeVaultApi $api, string $name): ?array
    {
        $match = null;
        foreach ($api->calls as $call) {
            if ($call['call'] === $name) {
                $match = $call;
            }
        }

        return $match;
    }

    public function test_channel_created_when_missing_then_cached(): void
    {
        $api = new FakeVaultApi();
        $vault = new TelegramVault('set1', $api->map());

        $msgId = $vault->putManifest('{"version":1}');

        self::assertSame('1', $msgId);
        self::assertSame(1, $api->createChannelCalls(), 'missing channel must be created exactly once');
        $find = $this->callOf($api, 'findChannel');
        self::assertNotNull($find);
        self::assertSame('teleframe-backup:set1', $find['args'][0], 'lookup uses the channel title');

        $vault->putManifest('{"version":2}');
        self::assertSame(1, $api->createChannelCalls(), 'resolution is lazy and the id is cached');
    }

    public function test_existing_channel_found_without_create(): void
    {
        $api = new FakeVaultApi();
        $api->addChannel('teleframe-backup:set1');
        $vault = new TelegramVault('set1', $api->map());

        $vault->putChunk('hash-a', 'bytes-a');

        self::assertSame(0, $api->createChannelCalls(), 'existing channel must not be recreated');
        $send = $this->callOf($api, 'sendDocument');
        self::assertNotNull($send);
        self::assertSame(
            ['_' => 'inputPeerChannel', 'channel_id' => 1000, 'access_hash' => 4242],
            $send['args'][0],
            'calls address the found channel as an inputPeerChannel with its access hash',
        );
    }

    public function test_chunk_roundtrip_uploads_named_by_hash(): void
    {
        $api = new FakeVaultApi();
        $vault = new TelegramVault('set1', $api->map());

        $msgId = $vault->putChunk('hash-a', 'bytes-a');

        self::assertSame('1', $msgId);
        $upload = $this->callOf($api, 'uploadBytes');
        self::assertNotNull($upload);
        self::assertSame(['hash-a', 'bytes-a'], $upload['args'], 'chunk uploads under its hash as file name');
        $send = $this->callOf($api, 'sendDocument');
        self::assertNotNull($send);
        self::assertSame('hash-a', $send['args'][2], 'caption is the hash');
        self::assertSame('hash-a', $send['args'][1]['name'], 'document file name is the hash');
        self::assertSame('bytes-a', $vault->getChunk('hash-a'));
    }

    public function test_get_chunk_rejects_longer_names_sharing_the_hash_prefix(): void
    {
        $api = new FakeVaultApi();
        $channelId = $api->addChannel('teleframe-backup:set1');
        $api->postDocument($channelId, 'hash-a-v2', 'other bytes');

        $vault = new TelegramVault('set1', $api->map());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hash-a');

        $vault->getChunk('hash-a');
    }

    public function test_manifest_latest_wins(): void
    {
        $api = new FakeVaultApi();
        $vault = new TelegramVault('set1', $api->map());

        $vault->putManifest('{"version":1}');
        $vault->putManifest('{"version":2}');

        self::assertSame(['version' => 2], $vault->getLatestManifest());
        $send = $this->callOf($api, 'sendText');
        self::assertNotNull($send);
        self::assertSame('TBMANIFEST1:' . base64_encode('{"version":2}'), $send['args'][1], 'manifest text is marker + base64 json');
    }

    public function test_manifest_prefix_detection_uses_string_functions_not_regex(): void
    {
        $api = new FakeVaultApi();
        $channelId = $api->addChannel('teleframe-backup:set1');

        // Decoy: marker NOT at position 0 but carrying a HIGHER (later) id.
        $api->postText($channelId, 'noteTBMANIFEST1:' . base64_encode('{"version":9}'));
        $api->postText($channelId, 'TBMANIFEST1:' . base64_encode('{"version":2}'));

        $vault = new TelegramVault('set1', $api->map());

        self::assertSame(
            ['version' => 2],
            $vault->getLatestManifest(),
            'contains-but-not-startswith decoys are ignored even when newer'
        );
    }

    public function test_latest_manifest_null_when_channel_has_no_manifests(): void
    {
        $api = new FakeVaultApi();
        $channelId = $api->addChannel('teleframe-backup:set1');
        $api->postDocument($channelId, 'hash-a', 'bytes-a');

        $vault = new TelegramVault('set1', $api->map());

        self::assertNull($vault->getLatestManifest());
    }

    public function test_corrupt_manifest_payload_fails_loud(): void
    {
        $api = new FakeVaultApi();
        $channelId = $api->addChannel('teleframe-backup:set1');
        $api->postText($channelId, 'TBMANIFEST1:not-base64-json!!');

        $vault = new TelegramVault('set1', $api->map());

        $this->expectException(RuntimeException::class);
        $vault->getLatestManifest();
    }

    public function test_scope_api_map_exposes_the_eight_callables(): void
    {
        $map = TelegramVault::scopeApi(new FakeUserScope());
        ksort($map);

        self::assertSame(
            ['createChannel', 'deleteMessages', 'findChannel', 'findMessagesByName', 'listHistoryPage', 'sendDocument', 'sendText', 'uploadBytes'],
            array_keys($map)
        );
        foreach ($map as $callable) {
            self::assertIsCallable($callable);
        }
    }

    public function test_list_all_entries_is_uncapped_whereas_find_messages_by_name_caps_at_search_limit(): void
    {
        $api = new FakeVaultApi();
        $channelId = $api->addChannel('teleframe-backup:set1');
        foreach (range(1, 150) as $i) {
            $api->postDocument($channelId, 'hash-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'bytes-' . $i);
        }
        $vault = new TelegramVault('set1', $api->map());

        self::assertCount(100, $vault->findMessagesByName(''), 'SEARCH_LIMIT still caps the search-backed listing (find path)');
        $entries = $vault->listAllEntries();
        self::assertCount(150, $entries, 'the prune-path walk is uncapped: every entry is visible');
        self::assertSame('hash-150', $entries[0]['name'], 'newest first, like findMessagesByName');
        self::assertSame('hash-001', $entries[149]['name'], 'oldest last');
        foreach ($entries as $entry) {
            self::assertSame(['id', 'name'], array_keys($entry), 'findMessagesByName entry shape');
            self::assertIsString($entry['id']);
        }
    }

    public function test_find_messages_by_name_with_empty_prefix_lists_everything(): void
    {
        $api = new FakeVaultApi();
        $channelId = $api->addChannel('teleframe-backup:set1');
        $api->postDocument($channelId, 'hash-a', 'bytes-a');
        $api->postDocument($channelId, 'hash-b', 'bytes-b');
        $api->postText($channelId, 'TBMANIFEST1:' . base64_encode('{"version":1}'));

        $vault = new TelegramVault('set1', $api->map());
        $names = array_column($vault->findMessagesByName(''), 'name');

        self::assertSame(['TBMANIFEST1:' . base64_encode('{"version":1}'), 'hash-b', 'hash-a'], $names);
    }

    public function test_delete_removes_exact_name_chunk_via_delete_messages(): void
    {
        $api = new FakeVaultApi();
        $channelId = $api->addChannel('teleframe-backup:set1');
        $api->postDocument($channelId, 'hash-a', 'bytes-a');
        $api->postDocument($channelId, 'hash-b', 'bytes-b');

        $vault = new TelegramVault('set1', $api->map());

        $ids = [];
        foreach ($vault->findMessagesByName('') as $entry) {
            $ids[$entry['name']] = (int) $entry['id'];
        }

        $vault->delete('hash-a');

        $delete = $this->callOf($api, 'deleteMessages');
        self::assertNotNull($delete, 'delete must ride the deleteMessages api call');
        self::assertSame(
            ['_' => 'inputPeerChannel', 'channel_id' => 1000, 'access_hash' => 4242],
            $delete['args'][0]
        );
        self::assertSame([$ids['hash-a']], $delete['args'][1], 'only the orphaned name\'s message id is revoked');

        self::assertSame(['hash-b'], array_column($vault->findMessagesByName('hash-'), 'name'), 'kept chunk survives');
        self::assertSame('bytes-b', $vault->getChunk('hash-b'));
        $this->expectException(RuntimeException::class);
        $vault->getChunk('hash-a');
    }

    public function test_delete_is_idempotent_for_absent_names(): void
    {
        $api = new FakeVaultApi();
        $vault = new TelegramVault('set1', $api->map());

        $vault->delete('never-stored-hash');

        self::assertNull($this->callOf($api, 'deleteMessages'), 'no deleteMessages call when nothing matches');
    }

    public function test_constructor_rejects_incomplete_api_map(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('findChannel');

        new TelegramVault('set1', ['uploadBytes' => static fn () => []]);
    }
}
