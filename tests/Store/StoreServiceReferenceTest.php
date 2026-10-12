<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * StoreService::publishByReference (Cycle 14): the "hash stays in the
 * database, the file stays in the channel" path. The service resolves the
 * tf_store_media row and delegates to the channel; a row that is not
 * indexed must throw instead of quietly falling back to a re-upload,
 * because a silent re-upload is exactly the cost this feature removes.
 */
final class StoreServiceReferenceTest extends TestCase
{
    private const ACCOUNT = 7;

    private const BUCKET = 'data:pics';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private StoreService $service;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'svc-ref');
        $this->index = new MemoryStoreIndex;
        $this->service = new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 1024 * 1024),
            queue: new SyncUploadQueue,
        );
    }

    /** @return array<string, mixed> */
    private static function peer(int $channelId = 900, int $accessHash = 4242): array
    {
        return ['_' => 'inputPeerChannel', 'channel_id' => $channelId, 'access_hash' => $accessHash];
    }

    private function indexMedia(int $msgId, int $docId = 777, string $fileReference = 'AQAD-file-reference-bytes'): void
    {
        $this->index->upsertMedia(self::ACCOUNT, self::BUCKET, $msgId, [
            'media_type' => 'document',
            'doc_id' => $docId,
            'access_hash' => 123456789,
            'file_reference' => $fileReference,
            'size' => 4096,
            'mime' => 'image/jpeg',
            'content_sha256' => null,
            'indexed_at_unix' => 1758000000,
        ]);
    }

    public function test_publish_by_reference_uses_the_indexed_hash_and_uploads_nothing(): void
    {
        $this->indexMedia(42);

        $msgId = $this->service->publishByReference(self::BUCKET, 42, self::peer(), 'public post');

        self::assertGreaterThan(0, $msgId);
        self::assertSame(0, $this->api->uploadPartCalls);
        self::assertCount(1, $this->api->referenceSends);
        $send = $this->api->referenceSends[0];
        self::assertSame('AQAD-file-reference-bytes', $send['media']['id']['file_reference']);
        self::assertSame(777, $send['media']['id']['id']);
        self::assertSame('public post', $send['caption']);
    }

    public function test_publish_by_reference_defaults_to_an_empty_caption(): void
    {
        $this->indexMedia(42);

        $this->service->publishByReference(self::BUCKET, 42, self::peer());

        self::assertSame('', $this->api->referenceSends[0]['caption']);
    }

    public function test_unindexed_message_throws_and_never_re_uploads(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tf_store_media');
        try {
            $this->service->publishByReference(self::BUCKET, 99, self::peer());
        } finally {
            self::assertSame(0, $this->api->uploadPartCalls, 'a missing index row must not trigger an upload');
            self::assertSame([], $this->api->referenceSends);
        }
    }

    public function test_media_row_of_another_account_is_not_reachable(): void
    {
        $this->index->upsertMedia(self::ACCOUNT + 1, self::BUCKET, 42, [
            'media_type' => 'document',
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'size' => 4096,
            'mime' => 'image/jpeg',
        ]);

        $this->expectException(RuntimeException::class);
        $this->service->publishByReference(self::BUCKET, 42, self::peer());
    }

    public function test_publish_by_reference_works_after_the_local_copy_is_gone(): void
    {
        // No local bytes, no cache entry, no object row: the Telegram copy
        // plus the indexed hash are the only durable reference, which is the
        // whole point of the user's "we delete something in main laravel
        // storage but its hash in telegram is still in database".
        $this->indexMedia(7, docId: 5150, fileReference: 'cold-ref');

        $msgId = $this->service->publishByReference(self::BUCKET, 7, self::peer(), 'from cold storage');

        self::assertGreaterThan(0, $msgId);
        self::assertSame(0, $this->api->uploadPartCalls);
        self::assertSame('cold-ref', $this->api->referenceSends[0]['media']['id']['file_reference']);
    }
}
