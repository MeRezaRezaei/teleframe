<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Facades\TStore;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;

/**
 * CLAIM 4: `teleframe:tstore publish` — the Artisan face of
 * `StoreService::publishByReference()`.
 *
 * The capability works and is exactly right: it hands Telegram's own
 * `file_reference` to the wire with no download and no re-upload, and throws
 * loudly rather than silently re-uploading. But it had NO Artisan action and
 * NO route, and `TStore::publishByReference()` was an `@method` docblock only
 * — so the owner sentence ("for posting them in our public channel we just
 * pass the hash") was usable only by writing PHP. This pins the CLI face.
 *
 * Lives under tests/Schema because that is this change's declared slice; the
 * subject is the command, not the schema.
 */
final class TstorePublishActionTest extends TestCase
{
    private const ACCOUNT = 7;

    private const BUCKET = 'data:pics';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FakeStoreApi(scope: 'cli-publish');
        $this->index = new MemoryStoreIndex;

        $this->app->make(Kernel::class)->registerCommand(new TstoreCommand);

        $channel = fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap());

        $this->app->bind(TstoreCommand::CHANNEL_FACTORY_KEY, $channel);
        $this->app->instance(TstoreCommand::INDEX_KEY, $this->index);
        $this->app->instance(TstoreCommand::HOT_CACHE_KEY, new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024));
        $this->app->instance(TstoreCommand::QUEUE_KEY, new SyncUploadQueue);
        $this->app->instance(TstoreCommand::SCHEDULES_KEY, new NullSchedulePublisher);
        $this->app->instance(TstoreCommand::SERVICE_KEY, new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: $channel,
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
            queue: new SyncUploadQueue,
        ));
    }

    /** @return array<string, mixed> */
    private static function peer(int $channelId = 900, int $accessHash = 4242): array
    {
        return ['_' => 'inputPeerChannel', 'channel_id' => $channelId, 'access_hash' => $accessHash];
    }

    private function indexMedia(int $msgId, int $docId = 777): void
    {
        $this->index->upsertMedia(self::ACCOUNT, self::BUCKET, $msgId, [
            'media_type' => 'document',
            'doc_id' => $docId,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
        ]);
    }

    /**
     * The happy path: the command reaches publishByReference and reports the
     * new message id.
     */
    public function test_publish_reposts_an_indexed_reference_by_hash(): void
    {
        $this->indexMedia(101);

        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'bucket' => self::BUCKET,
            'name' => '101', // source message id
            'source' => json_encode(self::peer(), JSON_THROW_ON_ERROR),
        ]);
        $out = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit, $out);
        self::assertStringContainsString('Published', $out);
        // The posted message id must be reported, not just "ok".
        self::assertMatchesRegularExpression('/\b\d{2,}\b/', $out);
    }

    /**
     * Argument validation: each missing piece is refused with a named, useful
     * message rather than reaching the wire.
     */
    public function test_publish_refuses_missing_arguments(): void
    {
        // No bucket.
        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'name' => '101',
            'source' => json_encode(self::peer(), JSON_THROW_ON_ERROR),
        ]);
        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('bucket', Artisan::output());

        // No message id.
        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'bucket' => self::BUCKET,
            'source' => json_encode(self::peer(), JSON_THROW_ON_ERROR),
        ]);
        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('message id', Artisan::output());

        // No peer.
        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'bucket' => self::BUCKET,
            'name' => '101',
        ]);
        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('peer', Artisan::output());
    }

    /**
     * A non-numeric message id is refused as a message-id problem, not
     * silently coerced to 0.
     */
    public function test_publish_refuses_a_non_numeric_message_id(): void
    {
        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'bucket' => self::BUCKET,
            'name' => 'not-a-number',
            'source' => json_encode(self::peer(), JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('message id', Artisan::output());
    }

    /**
     * An unknown bucket must be refused LOUDLY. `publishByReference` throws
     * rather than silently re-uploading, and the CLI must not swallow that
     * into a success.
     */
    public function test_publish_refuses_an_unindexed_source_loudly(): void
    {
        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'bucket' => self::BUCKET,
            'name' => '999', // never indexed
            'source' => json_encode(self::peer(), JSON_THROW_ON_ERROR),
        ]);
        $out = Artisan::output();

        self::assertSame(TstoreCommand::FAILURE, $exit, 'an unindexed source must not report success');
        self::assertStringContainsString('tf_store_media', $out, 'the loud refusal must name the missing reference');
        self::assertStringNotContainsString('Published', $out);
    }

    /**
     * A malformed destination peer is refused before any wire call — the peer
     * JSON is host input, so it is validated, not trusted.
     */
    public function test_publish_refuses_a_malformed_peer_loudly(): void
    {
        $this->indexMedia(101);

        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'bucket' => self::BUCKET,
            'name' => '101',
            'source' => '{not json',
        ]);
        $out = Artisan::output();

        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('peer', $out);
    }

    /**
     * A peer that parses but is not an inputPeer is refused: publishing to
     * "nothing" must not fall through to the wire.
     */
    public function test_publish_refuses_a_non_input_peer_loudly(): void
    {
        $this->indexMedia(101);

        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'publish',
            'bucket' => self::BUCKET,
            'name' => '101',
            'source' => '{"_":"inputUserSelf"}',
        ]);
        $out = Artisan::output();

        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('peer', $out);
    }

    /**
     * `publish` must be a listed action, and the TStore facade must carry a
     * REAL forwarding method — an `@method` docblock is not callable, which is
     * precisely the gap this closes.
     */
    public function test_publish_is_a_listed_action_and_the_facade_has_a_real_method(): void
    {
        // A listed action: the signature is the single source of truth for
        // which verbs the command accepts, so assert against it directly.
        $command = new TstoreCommand('teleframe:tstore');
        $signature = new \ReflectionProperty($command, 'signature');
        $signature->setAccessible(true);
        self::assertStringContainsString('publish', (string) $signature->getValue($command));

        // And the facade must carry a REAL forwarding method — an `@method`
        // docblock is not callable, which is precisely the gap this closes.
        $method = new \ReflectionMethod(TStore::class, 'publishByReference');
        self::assertTrue($method->isStatic(), 'TStore::publishByReference must be a real static method, not a docblock');
    }
}
