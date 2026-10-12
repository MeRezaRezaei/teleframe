<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;

/**
 * Channel-level reference publish (Cycle 14): posting a file that Telegram
 * already holds must go out as inputMediaDocument + inputDocument and touch
 * the upload path ZERO times. The uploadPart call counter in FakeStoreApi is
 * the contract guard — if a future change ever routes a reference send
 * through the bytes, this test fails instead of silently re-uploading
 * gigabytes.
 *
 * The wire SHAPE is pinned against the vendored spec and the real encoder in
 * TelegramStoreChannelWireKeyTest; what matters here is that the publish path
 * resolves the SAME document identity the index row names.
 */
final class TelegramStoreChannelReferenceTest extends TestCase
{
    private const SOURCE_BUCKET = 'data:pics';

    private FakeStoreApi $api;

    private TelegramStoreChannel $channel;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'ref');
        $this->channel = new TelegramStoreChannel(self::SOURCE_BUCKET, $this->api->apiMap());
    }

    /** @return array<string, mixed> */
    private static function peer(int $channelId = 900, int $accessHash = 4242): array
    {
        return ['_' => 'inputPeerChannel', 'channel_id' => $channelId, 'access_hash' => $accessHash];
    }

    private function reference(): MediaReference
    {
        return MediaReference::fromIndexRow([
            'media_type' => 'document',
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'size' => 4096,
            'mime' => 'image/jpeg',
        ]);
    }

    public function test_send_reference_posts_the_file_reference_without_uploading_bytes(): void
    {
        $msgId = $this->channel->sendReference(self::peer(), $this->reference(), 'holiday.jpg');

        self::assertGreaterThan(0, $msgId);
        self::assertSame(0, $this->api->uploadPartCalls, 'reference publish must not upload a single part');
        self::assertCount(1, $this->api->referenceSends);

        $send = $this->api->referenceSends[0];
        self::assertSame(self::peer(), $send['peer']);
        self::assertSame('holiday.jpg', $send['caption']);
        self::assertSame('inputMediaDocument', $send['media']['_']);
        // inputMediaDocument#a8763ab5 declares `id:InputDocument` — not the
        // `document`/`force_file` of the uploaded-document shape it used to copy.
        self::assertArrayNotHasKey('document', $send['media']);
        self::assertArrayNotHasKey('force_file', $send['media']);
        self::assertSame('inputDocument', $send['media']['id']['_']);
        self::assertSame(777, $send['media']['id']['id']);
        self::assertSame(123456789, $send['media']['id']['access_hash']);
        self::assertSame('AQAD-file-reference-bytes', $send['media']['id']['file_reference']);
    }

    public function test_reference_send_keeps_the_same_document_identity_on_the_wire(): void
    {
        $sourceMsgId = $this->api->uploadDocument('source.jpg', 'jpeg-bytes');
        $row = $this->api->wireMessageRow('source.jpg');
        self::assertNotNull($row);
        $document = $row['media']['document'];
        $ref = MediaReference::fromDocument($document);

        $newMsgId = $this->channel->sendReference(self::peer(), $ref, 'republished');

        self::assertNotSame($sourceMsgId, $newMsgId);
        self::assertSame(0, $this->api->uploadPartCalls);

        $republished = null;
        foreach ($this->api->messages as $message) {
            if ($message['id'] === $newMsgId) {
                $republished = $message;
            }
        }
        self::assertNotNull($republished);
        self::assertSame($document['id'], $republished['doc_id'], 'same Telegram document, new message');
        self::assertSame('jpeg-bytes', $republished['bytes'], 'bytes served from the existing document, not re-uploaded');
    }

    public function test_target_peer_without_channel_id_is_refused_loudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('peer');
        $this->channel->sendReference(['access_hash' => 1], $this->reference(), 'cap');
    }

    public function test_channel_contract_requires_the_send_reference_callable(): void
    {
        $map = $this->api->apiMap();
        unset($map['sendReference']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sendReference');
        new TelegramStoreChannel(self::SOURCE_BUCKET, $map);
    }

    public function test_send_reference_does_not_reuse_the_bucket_channel_peer(): void
    {
        $this->channel->sendReference(self::peer(channelId: 555, accessHash: 666), $this->reference(), 'cap');

        $send = $this->api->referenceSends[0];
        self::assertSame(555, $send['peer']['channel_id']);
        self::assertSame(666, $send['peer']['access_hash']);
        self::assertNotSame($this->channel->inputPeer()['channel_id'], $send['peer']['channel_id']);
    }
}
