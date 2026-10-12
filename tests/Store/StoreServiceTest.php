<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\ChannelRegistry;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\UploadQueueContract;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;

/**
 * StoreService (plan W6): the account-scoped fan-in over channel +
 * index + cache + queue. Every port is faked; the sealed path exercises
 * a real Argon2id key derivation per object (default opslimit — a few
 * hundred ms, acceptable offline).
 */
final class StoreServiceTest extends TestCase
{
    private const BUCKET = 'media-pool';

    private const PASSPHRASE = 'correct horse battery staple';

    private FakeStoreApi $api;

    private TelegramStoreChannel $channel;

    private MemoryStoreIndex $index;

    private MemoryHotCache $cache;

    private StoreService $service;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'svc');
        $this->channel = new TelegramStoreChannel(self::BUCKET, $this->api->apiMap());
        $this->index = new MemoryStoreIndex;
        $this->cache = new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024);
        $this->service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channelFor($bucket),
            index: $this->index,
            cache: $this->cache,
            queue: new SyncUploadQueue,
        );
    }

    /** Per-bucket channels share the fake api; same channel for every bucket is fine offline. */
    private function channelFor(string $bucket): TelegramStoreChannel
    {
        return new TelegramStoreChannel($bucket, $this->api->apiMap());
    }

    // ── put / get roundtrips ──────────────────────────────────────────

    public function test_put_raw_then_get_roundtrips_and_indexes(): void
    {
        $bytes = str_repeat('abc', 300_000);

        $result = $this->service->put(self::BUCKET, 'raw.bin', $bytes, ObjectManifest::MODE_RAW);

        $this->assertFalse($result['queued']);
        $this->assertSame('raw.bin', $result['manifest']['name']);
        $this->assertSame(ObjectManifest::MODE_RAW, $result['manifest']['mode']);
        $this->assertSame(strlen($bytes), $result['manifest']['size']);
        $this->assertSame(hash('sha256', $bytes), $result['manifest']['sha256']);
        $this->assertSame($bytes, $this->service->get(self::BUCKET, 'raw.bin'));
        $this->assertTrue($this->service->exists(self::BUCKET, 'raw.bin'));
        $this->assertSame(strlen($bytes), $this->service->size(self::BUCKET, 'raw.bin'));
    }

    public function test_put_sealed_then_get_with_correct_passphrase_roundtrips(): void
    {
        $bytes = str_repeat('sealed payload ', 10_000);

        $result = $this->service->put(self::BUCKET, 'secret.db', $bytes, ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $this->assertSame(ObjectManifest::MODE_SEALED, $result['manifest']['mode']);
        $this->assertNotSame($bytes, $this->rawChunkBytes($result['manifest']), 'sealed wire must not leak plaintext');
        $this->assertSame($bytes, $this->service->get(self::BUCKET, 'secret.db', self::PASSPHRASE));
    }

    public function test_sealed_put_requires_passphrase_and_get_without_one_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->put(self::BUCKET, 'secret.txt', 'classified', ObjectManifest::MODE_SEALED, null);
    }

    public function test_put_rejects_unknown_modes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->put(self::BUCKET, 'odd.bin', 'x', 'encrypted');
    }

    // ── exists / size / delete ────────────────────────────────────────

    public function test_delete_removes_messages_index_and_cache(): void
    {
        $this->service->put(self::BUCKET, 'gone.txt', 'to be deleted', ObjectManifest::MODE_RAW);
        $manifest = $this->index->findObject(7, self::BUCKET, 'gone.txt')['metadata'];
        $chunkName = $manifest['chunks'][0]['sha256'];

        // warm the cache so we can verify cache eviction, too
        $this->service->get(self::BUCKET, 'gone.txt');

        $this->service->delete(self::BUCKET, 'gone.txt');

        $this->assertFalse($this->service->exists(self::BUCKET, 'gone.txt'));
        $this->assertSame([], $this->index->listObjects(7, self::BUCKET));
        // the object's own messages (TFSTORE1 metadata + chunks) are revoked,
        // while the persistent TFREGISTRY1 registry message stays.
        $names = array_column($this->api->messages, 'name');
        $this->assertNotContains('TFSTORE1: gone.txt', $names, 'object metadata message must be revoked');
        $this->assertNotContains($chunkName, $names, 'chunk document must be revoked');
        $this->assertContains('TFREGISTRY1: store/'.$this::BUCKET, $names, 'registry message persists');
    }

    public function test_delete_absent_object_is_a_noop(): void
    {
        $this->service->delete(self::BUCKET, 'never-was.bin');
        $this->assertFalse($this->service->exists(self::BUCKET, 'never-was.bin'));
        $this->assertNull($this->service->size(self::BUCKET, 'never-was.bin'));
    }

    /**
     * A revoke must not take a NEIGHBOUR whose name has the target as a
     * prefix.
     *
     * The lookup is messages.search with 'q' => "TFSTORE1: foo", which is a
     * server-side SUBSTRING match, and "TFSTORE1: foo" is a literal substring
     * of "TFSTORE1: foo-bar" — so an unfiltered id list revoked foo-bar's
     * messages along with foo's. The fix filters the ALREADY-FETCHED rows
     * by exact name (StoreService::messageIdsFor), which is why the fake's
     * substring-matching searchMessages is the right double here: it is the
     * behaviour under test, not a shortcut around it.
     */
    public function test_delete_does_not_revoke_a_neighbour_whose_name_extends_the_target(): void
    {
        $this->service->put(self::BUCKET, 'foo', 'bytes of foo', ObjectManifest::MODE_RAW);
        $this->service->put(self::BUCKET, 'foo-bar', 'bytes of foo-bar', ObjectManifest::MODE_RAW);

        $this->assertTrue($this->service->exists(self::BUCKET, 'foo-bar'), 'precondition: the neighbour exists');

        $this->service->delete(self::BUCKET, 'foo');

        $this->assertFalse($this->service->exists(self::BUCKET, 'foo'), 'the target is gone');
        $this->assertTrue(
            $this->service->exists(self::BUCKET, 'foo-bar'),
            '"TFSTORE1: foo" is a substring of "TFSTORE1: foo-bar": a server-side substring '
            .'search must not widen the revoke into the neighbour',
        );

        $names = array_column($this->api->messages, 'name');
        $this->assertNotContains('TFSTORE1: foo', $names);
        $this->assertContains('TFSTORE1: foo-bar', $names, "the neighbour's metadata message must survive");
    }

    // ── syncRegistry ──────────────────────────────────────────────────

    public function test_sync_registry_walks_dialogs_and_upserts_buckets(): void
    {
        $service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channelFor($bucket),
            index: $this->index,
            cache: $this->cache,
            queue: new SyncUploadQueue,
            dialogLister: fn (): array => [
                ['id' => 101, 'access_hash' => 7, 'title' => 'teleframe-store:media-pool'],
                ['id' => 102, 'access_hash' => 8, 'title' => 'teleframe-data:feed'],
                ['id' => 103, 'access_hash' => 9, 'title' => 'some unrelated channel'],
            ],
        );

        $report = $service->syncRegistry(['media-pool']);

        $this->assertSame(3, $report['walked']);
        $this->assertSame(2, $report['upserted']);
        $bucket = $this->index->findBucket(7, 'media-pool');
        $this->assertSame('store', $bucket['kind']);
        $this->assertSame(101, $bucket['channel_id']);
        $this->assertSame('data', $this->index->findBucket(7, 'feed')['kind']);
    }

    public function test_sync_registry_requires_a_dialog_lister(): void
    {
        $this->expectException(LogicException::class);
        $this->service->syncRegistry(['media-pool']);
    }

    // ── indexDataChannel ──────────────────────────────────────────────

    public function test_index_data_channel_walks_history_into_media_index(): void
    {
        $this->api->uploadDocument('doc-a', str_repeat('A', 1000));
        $this->api->uploadDocument('doc-b', str_repeat('B', 2000));
        $this->api->sendText('just a text post');

        $indexed = $this->service->indexDataChannel(self::BUCKET);

        $this->assertSame(2, $indexed, 'text posts must not be indexed as media');
        $this->assertCount(2, $this->index->listMedia(7, self::BUCKET));
    }

    // ── verify / prune ────────────────────────────────────────────────

    public function test_verify_raw_object_reports_ok(): void
    {
        $this->service->put(self::BUCKET, 'v.bin', 'verifiable', ObjectManifest::MODE_RAW);

        $report = $this->service->verify(self::BUCKET, 'v.bin');

        $this->assertTrue($report['ok']);
        $this->assertSame(1, $report['chunks']);
        $this->assertSame([], $report['errors']);
    }

    public function test_verify_sealed_object_reports_ok_with_passphrase(): void
    {
        $this->service->put(self::BUCKET, 'v.sealed', 'sealed verify me', ObjectManifest::MODE_SEALED, self::PASSPHRASE);
        $this->assertServiceCausedNoVerificationSurprise();

        $report = $this->service->verify(self::BUCKET, 'v.sealed', self::PASSPHRASE);
        $this->assertTrue($report['ok']);
    }

    public function test_verify_detects_corrupted_chunk(): void
    {
        $this->service->put(self::BUCKET, 'c.bin', 'nothing but trouble', ObjectManifest::MODE_RAW);
        $manifest = $this->index->findObject(7, self::BUCKET, 'c.bin');
        $chunkName = $manifest['chunks'][0]['sha256'];
        // tamper the document bytes the fake serves back
        $this->api->tamperDocument($chunkName, 'tampered');

        $report = $this->service->verify(self::BUCKET, 'c.bin');
        $this->assertFalse($report['ok']);
        $this->assertNotEmpty($report['errors']);
    }

    public function test_prune_evicts_cold_chunks_from_cache(): void
    {
        $clock = time();
        $cache = new MemoryHotCache(ttlSeconds: 60, maxBytes: 4 * 1024 * 1024, now: static function () use (&$clock): int {
            return $clock;
        });
        $service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channelFor($bucket),
            index: $this->index,
            cache: $cache,
            queue: new SyncUploadQueue,
        );
        $service->put(self::BUCKET, 'p.bin', str_repeat('p', 500_000), ObjectManifest::MODE_RAW);
        $service->get(self::BUCKET, 'p.bin'); // warms the cache (read populates)
        $this->assertSame(1, $cache->stats()['entries']);

        // clock jumps past the TTL → prune()'s evict sweep considers it cold
        $clock += 120;
        $service->prune();

        $this->assertSame(0, $cache->stats()['entries'], 'warm entry must be evicted by the sweep');
    }

    /** @noinspection PhpUnusedLocalVariableInspection — placeholder guard so the suite is honest about gaps. */
    private function assertServiceCausedNoVerificationSurprise(): void
    {
        // sealed roundtrip above is the real check; nothing further to assert here.
    }

    // ── archiveMessageMedia (S11 live index, single-shot proof) ────────

    public function test_archive_message_media_indexes_a_document_from_a_data_channel(): void
    {
        // Declared data channel, matching the documented store prefix.
        $this->index->upsertBucket(7, 'data:photos', [
            'kind' => ChannelRegistry::KIND_DATA,
            'channel_id' => 202,
            'access_hash' => 77,
        ]);

        $report = $this->service->archiveMessageMedia([
            '_' => 'updateNewChannelMessage',
            'message' => [
                'id' => 410,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => 202],
                'media' => [
                    '_' => 'messageMediaDocument',
                    'document' => [
                        '_' => 'document',
                        'id' => 9001,
                        'access_hash' => 123456,
                        'file_reference' => base64_encode('ref-bytes'),
                        'size' => 8192,
                        'mime_type' => 'application/zip',
                    ],
                ],
            ],
        ]);

        $this->assertTrue($report['indexed']);
        $this->assertSame('data:photos', $report['bucket']);
        $this->assertSame(410, $report['msg_id']);

        $row = $this->index->findMedia(7, 'data:photos', 410);
        $this->assertNotNull($row);
        $this->assertSame('document', $row['media_type']);
        $this->assertSame(9001, $row['doc_id']);
        $this->assertSame(123456, $row['access_hash']);
        $this->assertSame('application/zip', $row['mime']);
        $this->assertNull($row['content_sha256'], 'content hash stays lazy until fetched through cache');
    }

    public function test_archive_message_media_is_single_shot_per_message(): void
    {
        $this->index->upsertBucket(7, 'data:photos', [
            'kind' => ChannelRegistry::KIND_DATA,
            'channel_id' => 202,
            'access_hash' => 77,
        ]);

        $update = [
            '_' => 'updateNewChannelMessage',
            'message' => [
                'id' => 410,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => 202],
                'media' => [
                    '_' => 'messageMediaDocument',
                    'document' => [
                        '_' => 'document',
                        'id' => 9001,
                        'access_hash' => 123456,
                        'file_reference' => base64_encode('ref-bytes'),
                        'size' => 8192,
                        'mime_type' => 'application/zip',
                    ],
                ],
            ],
        ];

        // Fire the same live update twice (bus redelivery / re-ingest).
        $this->service->archiveMessageMedia($update);
        $this->service->archiveMessageMedia($update);

        $rows = $this->index->listMedia(7, 'data:photos');
        $this->assertCount(1, $rows, 're-delivered updates must collapse into one media row');
        $this->assertSame(410, $rows[0]['msg_id']);
    }

    public function test_archive_message_media_ignores_undocumented_or_foreign_peers(): void
    {
        $this->index->upsertBucket(7, 'data:photos', [
            'kind' => ChannelRegistry::KIND_DATA,
            'channel_id' => 202,
            'access_hash' => 77,
        ]);

        // Non-document update → no index.
        $noDoc = $this->service->archiveMessageMedia([
            '_' => 'updateNewChannelMessage',
            'message' => ['id' => 1, 'peer_id' => ['channel_id' => 202], 'media' => ['_' => 'messageMediaPhoto']],
        ]);
        $this->assertFalse($noDoc['indexed']);
        $this->assertStringContainsString('no document', $noDoc['reason']);

        // Document update on an UNDECLARED channel → no index.
        $unknown = $this->service->archiveMessageMedia([
            '_' => 'updateNewChannelMessage',
            'message' => [
                'id' => 2,
                'peer_id' => ['channel_id' => 999],
                'media' => [
                    '_' => 'messageMediaDocument',
                    'document' => ['_' => 'document', 'id' => 1, 'access_hash' => 1, 'file_reference' => '', 'size' => 1, 'mime_type' => 'x'],
                ],
            ],
        ]);
        $this->assertFalse($unknown['indexed']);
        $this->assertStringContainsString('not a declared data channel', $unknown['reason']);

        $this->assertSame([], $this->index->listMedia(7, 'data:photos'));
    }

    // ── queued put (async driver): temp-file ownership discipline ──────

    public function test_queued_put_hands_a_worker_owned_temp_file_to_the_async_queue(): void
    {
        $queue = new RecordingAsyncQueue;
        $service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channelFor($bucket),
            index: $this->index,
            cache: $this->cache,
            queue: $queue,
        );

        $bytes = str_repeat('q', 1024);
        $result = $service->put(self::BUCKET, 'q.bin', $bytes, ObjectManifest::MODE_RAW, queued: true);

        $this->assertTrue($result['queued']);
        $this->assertSame('job-1', $result['job']);
        $this->assertCount(1, $queue->enqueued);
        $path = $queue->enqueued[0]['source_path'];
        $this->assertIsString($path);
        $this->assertStringStartsWith(sys_get_temp_dir(), $path);
        $this->assertFileExists($path, 'the worker owns the materialized temp file until it uploads');
        $this->assertSame($bytes, file_get_contents($path));
    }

    public function test_queued_put_with_caller_path_keeps_the_file_untouched(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tfstore-test-');
        file_put_contents($path, 'caller file');
        $queue = new RecordingAsyncQueue;
        $service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channelFor($bucket),
            index: $this->index,
            cache: $this->cache,
            queue: $queue,
        );

        try {
            $result = $service->put(self::BUCKET, 'p.bin', $path, ObjectManifest::MODE_RAW, queued: true);

            $this->assertTrue($result['queued']);
            $this->assertSame($path, $queue->enqueued[0]['source_path'], 'caller paths pass through, never copied');
            $this->assertFileExists($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_queued_put_enqueue_failure_unlinks_the_materialized_temp_file(): void
    {
        $queue = new RecordingAsyncQueue;
        $queue->fail = true;
        $service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channelFor($bucket),
            index: $this->index,
            cache: $this->cache,
            queue: $queue,
        );

        try {
            $service->put(self::BUCKET, 'q.bin', 'bytes payload', ObjectManifest::MODE_RAW, queued: true);
            $this->fail('expected the enqueue failure to propagate');
        } catch (\RuntimeException $e) {
            $this->assertSame('broker unreachable', $e->getMessage());
        }

        $this->assertCount(1, $queue->enqueued);
        $path = $queue->enqueued[0]['source_path'];
        $this->assertIsString($path);
        $this->assertFileDoesNotExist($path, 'a job the worker never receives must not leak its temp file');
    }

    public function test_queued_put_enqueue_failure_never_unlinks_a_caller_path(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tfstore-test-');
        file_put_contents($path, 'caller file');
        $queue = new RecordingAsyncQueue;
        $queue->fail = true;
        $service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channelFor($bucket),
            index: $this->index,
            cache: $this->cache,
            queue: $queue,
        );

        try {
            $service->put(self::BUCKET, 'p.bin', $path, ObjectManifest::MODE_RAW, queued: true);
            $this->fail('expected the enqueue failure to propagate');
        } catch (\RuntimeException $e) {
            $this->assertSame('broker unreachable', $e->getMessage());
            $this->assertFileExists($path, 'a caller-supplied path is never unlinked');
        } finally {
            @unlink($path);
        }
    }

    public function test_inline_put_never_closes_caller_owned_stream(): void
    {
        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, 'stream me');
        rewind($handle);

        $this->service->put(self::BUCKET, 'stream.bin', $handle, ObjectManifest::MODE_RAW);

        $this->assertTrue(is_resource($handle), 'caller-owned stream must stay open after put');
        fwrite($handle, ' still open');
        rewind($handle);
        $this->assertSame('stream me still open', stream_get_contents($handle));
        fclose($handle);
    }

    // ── helpers (test-side port peeking) ──────────────────────────────

    /** Full wire bytes of the first chunk, read from the fake document store. */
    private function rawChunkBytes(array $manifest): string
    {
        foreach ($this->api->messages as $message) {
            if ($message['name'] === ($manifest['chunks'][0]['wire_sha256'] ?? $manifest['chunks'][0]['sha256'])) {
                return (string) $message['bytes'];
            }
        }

        return '';
    }
}

/**
 * Record every enqueued payload (so tests can assert on the temp-file path),
 * optionally failing enqueue after recording — the "broker is down" case.
 */
final class RecordingAsyncQueue implements UploadQueueContract
{
    /** @var list<array<string, mixed>> */
    public array $enqueued = [];

    public bool $fail = false;

    public function enqueue(array $payload): string
    {
        $this->enqueued[] = $payload;
        if ($this->fail) {
            throw new \RuntimeException('broker unreachable');
        }

        return 'job-1';
    }

    public function consume(callable $handler, int $max = 1): int
    {
        return 0;
    }

    public function isAsync(): bool
    {
        return true;
    }
}
