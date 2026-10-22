<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use MeRezaRezaei\Teleframe\Tests\Support\SleepRecorder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * TelegramStoreChannel (plan W2): the channel-per-bucket wire surface —
 * lazy find-or-create channel id, putMessage/putChunk → sendMessage/
 * sendDocument (part-streamed), ensureRegistry idempotence, marker-prefix
 * find, offset_id history crawl with progress guards, chunk-document fetch
 * via getFile part loop (retried on injected flakiness), and the
 * api-map-shape contract (missing callable throws at construction).
 */
final class TelegramStoreChannelTest extends TestCase
{
    private FakeStoreApi $api;

    private TelegramStoreChannel $channel;

    private SleepRecorder $sleep;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'test');
        $this->sleep = new SleepRecorder;
        $this->channel = new TelegramStoreChannel('media-pool', $this->api->apiMap(), sleep: $this->sleep);
    }

    public function test_constructor_requires_all_wire_callables(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sendDocument');
        new TelegramStoreChannel('media-pool', ['findChannel' => static fn (): null => null]);
    }

    public function test_channel_id_is_lazily_found_or_created_once(): void
    {
        $this->api->channels[$this->api->channelTitleForBucket('media-pool')] = ['id' => 55, 'access_hash' => 77];

        $this->assertSame(55, $this->channel->channelId());
        $this->assertSame(55, $this->channel->channelId(), 'second call must reuse the cached id');
        $this->assertSame(0, $this->api->createChannelCalls, 'existing channel must not be recreated');

        $other = new TelegramStoreChannel('fresh-bucket', $this->api->apiMap());
        $this->assertSame(100, $other->channelId(), 'missing channel is created');
        $this->assertSame(1, $this->api->createChannelCalls);
    }

    public function test_put_message_posts_text_and_sends_to_channel_peer(): void
    {
        $id = $this->channel->putMessage("TFSTORE1\nbucket = media-pool\n");

        $this->assertSame(1, $id);
        $this->assertCount(1, $this->api->messages);
        $this->assertSame('TFSTORE1', $this->api->messages[0]['name']);
        $this->assertSame("TFSTORE1\nbucket = media-pool\n", $this->api->messages[0]['text']);
    }

    public function test_put_chunk_streams_parts_and_roundtrips_bytes(): void
    {
        $docName = 'doc-1';
        $payload = str_repeat('x', 2 * 512 * 1024 + 100); // 2 full parts + tail

        $msgId = $this->channel->putChunkBytes($docName, $payload);

        $this->assertSame(1, $msgId);
        $this->assertSame(3, $this->api->uploadPartCalls, '512 KiB parts + tail');
        $this->assertSame($payload, (string) $this->api->messages[0]['bytes'], 'sendDocument must concatenate stored parts');

        $fetched = $this->channel->fetchDocument($this->api->wireMessageRow($docName) ?? []);
        $this->assertSame($payload, $fetched, 'getFile part loop must stitch the document back byte-for-byte');
        $this->assertSame($docName, $this->api->messages[0]['name']);
    }

    public function test_fetch_document_returns_download_via_part_loop(): void
    {
        $docName = 'doc-large';
        $payload = str_repeat('x', 512 * 1024 * 2 + 7);

        $this->channel->putChunkBytes($docName, $payload);
        $this->assertSame($payload, $this->channel->fetchDocument($this->api->wireMessageRow($docName) ?? []));
    }

    public function test_fetch_document_without_media_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no downloadable document');
        $this->channel->fetchDocument(['id' => 1, 'name' => 'plain-text']);
    }

    public function test_ensure_registry_posts_only_once(): void
    {
        $first = $this->channel->ensureRegistry();
        $this->assertSame(1, $first);

        $second = $this->channel->ensureRegistry();
        $this->assertNull($second, 'registry already exists — idempotent no-op');
        $this->assertCount(1, $this->api->messages);
    }

    public function test_find_messages_by_marker_prefix(): void
    {
        $this->channel->putMessage("TFSTORE1\n"); // metadata
        $this->channel->putMessage("TFREGISTRY1\nbucket = media-pool\n"); // registry
        $this->channel->putChunkBytes('doc-1', 'hello');

        $store = $this->channel->findMessagesByName('TFSTORE1');
        $this->assertCount(1, $store);
        $this->assertSame('TFSTORE1', $store[0]['name']);
        $this->assertSame(1, $store[0]['id'], 'entry id must be present');

        $registry = $this->channel->findMessagesByName('TFREGISTRY1');
        $this->assertCount(1, $registry);
        $this->assertSame('TFREGISTRY1', $registry[0]['name']);

        $all = $this->channel->findMessagesByName('');
        $this->assertCount(3, $all, 'empty prefix lists every message');
    }

    public function test_list_all_entries_crawls_history_uncapped_with_progress_guard(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->channel->putMessage("TFSTORE1 entry {$i}\n");
        }

        $entries = $this->channel->listAllEntries(pageSize: 5);
        $this->assertCount(12, $entries);
        $this->assertSame('TFSTORE1 entry 12', $entries[0]['name'], 'newest first');

        // Dead history cursor (same oldest id forever) must abort loudly.
        $this->api->nextHistoryStall = true;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no forward progress');
        $this->channel->listAllEntries(pageSize: 5);
    }

    public function test_delete_messages_by_id(): void
    {
        $id = $this->channel->putMessage("TFSTORE1\n");
        $this->channel->deleteMessages([$id]);

        $this->assertCount(0, $this->api->messages, 'delete must revoke the posted message');
        $this->channel->deleteMessages([]); // no-op, must not error
    }

    public function test_upload_retries_after_injected_flaky_part(): void
    {
        $payload = str_repeat('z', 512 * 1024 + 1); // two parts
        $this->api->failNextUpload(floodSeconds: 5);

        $this->channel->putChunkBytes('flaky-doc', $payload); // resilient retry swallows the injected flood
        $this->assertSame($payload, (string) $this->api->messages[0]['bytes']);
        $this->assertContains(5, $this->sleep->seconds, 'flood wait must be slept via the injected sleeper');
    }

    public function test_fetch_retries_after_injected_flaky_download(): void
    {
        $payload = str_repeat('y', 512 * 1024 + 1); // two parts
        $this->channel->putChunkBytes('flaky-doc', $payload);
        $this->api->failNextDownload(floodSeconds: 3);

        $fetched = $this->channel->fetchDocument($this->api->wireMessageRow('flaky-doc') ?? []);
        $this->assertSame($payload, $fetched);
        $this->assertContains(3, $this->sleep->seconds);
    }

    public function test_channel_title_for_bucket_prefixes(): void
    {
        $this->assertSame(
            'teleframe-store:media-pool',
            TelegramStoreChannel::channelTitle('media-pool'),
        );
    }

    /**
     * The PRODUCTION fetch closure takes ZERO required parameters.
     *
     * Pinned by REFLECTION on the real producer, because the fake's entry
     * builder (tests/Support/FakeStoreApi::entry) declares `fn (): string`
     * and so agrees with a wrong-arity production closure by construction:
     * the two greens are independent, and the fake's cannot catch the real
     * one. A `string $fetchName` here raised ArgumentCountError at every
     * real call site — and because ArgumentCountError is an Error, not an
     * Exception, it escaped every catch (RuntimeException) in this
     * subsystem instead of failing as a clean per-chunk error.
     *
     * The Backup twin (Backup\TelegramVault) keeps its parameter on
     * purpose: its own caller passes a name and its fake indexes by it.
     */
    public function test_the_production_fetch_closure_takes_no_required_arguments(): void
    {
        // Driven through the PRODUCTION producer rowsToEntries(), NOT through
        // findMessagesByName(): the latter resolves rows via the injected
        // api['searchMessages'], and in a test that is the FAKE's closure
        // building its OWN `fn (): string` entry. Asserting arity on that
        // object is asserting the fake against itself — it stayed green
        // through the shipped `string $fetchName` regression.
        $payload = 'arity fixture bytes';
        $row = [
            '_' => 'message',
            'id' => 4242,
            'message' => 'arity-doc',
            'media' => ['document' => [
                '_' => 'document',
                'id' => 999,
                'access_hash' => 42,
                'file_reference' => 'ref-1',
                'size' => strlen($payload),
                'mime_type' => 'application/octet-stream',
                'attributes' => [[
                    '_' => 'documentAttributeFilename',
                    'file_name' => 'arity-doc',
                ]],
            ]],
        ];

        $scope = new class extends FakeUserScope
        {
            public function call(string $method, array $params = []): array
            {
                parent::call($method, $params);

                return $method === 'upload.getFile' ? ['bytes' => 'arity fixture bytes'] : [];
            }
        };

        $rowsToEntries = new \ReflectionMethod(TelegramStoreChannel::class, 'rowsToEntries');
        $rowsToEntries->setAccessible(true);
        $entries = $rowsToEntries->invoke(null, $scope, [$row]);

        $this->assertCount(1, $entries, 'precondition: the production producer made an entry');
        $entry = $entries[0];

        $this->assertIsCallable($entry['fetch'] ?? null);
        $this->assertSame(
            0,
            (new \ReflectionFunction($entry['fetch']))->getNumberOfRequiredParameters(),
            'StoreService::fetchChunkPlain() and TelegramTier::entryText() both call fetch() with '
            .'ZERO arguments; a required parameter here is an ArgumentCountError at the call site, '
            .'and an Error is NOT caught by catch (RuntimeException)',
        );
        $this->assertSame($payload, (string) ($entry['fetch'])(), 'and the zero-arg call must really work');
    }
}
