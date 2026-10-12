<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\RemoteTierContract;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Store\TelegramTier;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The HASH-ADDRESSED half of a cold copy: re-publish and revoke, with no local
 * index row anywhere (V1.2, 2026-09-27).
 *
 * `publishByReference()` used to REQUIRE a tf_store_media row, and `delete()`
 * used to return early without one. Both are bucket-storage API calls: "give me
 * this file by hash" and "remove this file". Requiring a local row for them
 * means the store cannot serve a host whose database was rebuilt elsewhere —
 * which is what "a mechanism of removing those that are no longer is
 * requesting but the api is intact" actually asks for.
 *
 * TWO SEAMS, and neither of them is the machine:
 *
 *  - ReferencePolicy decides WHETHER the remote tier may answer at all. The
 *    retaining arms below are pinned on the same wire, so "the default does not
 *    do this" is an assertion about the injection, not about the environment.
 *  - RemoteTierContract is the resolver itself, and it is stubbed in
 *    test_the_cold_branch_is_decided_by_the_injected_tier — a test in which the
 *    wire is EMPTY and the reference still resolves. If the branch could be
 *    decided by reachability, by an extension, or by a credential, that test
 *    would not be able to exist in CI at all. Three of its four methods are
 *    answered by that stub; the fourth, durableMediaFor(), is the CONTENT half
 *    and is answered with a refusal, because no test here reaches the reclaim
 *    path and a content hash this stub never computed is the one answer that
 *    must not be faked. Every other test in this file uses the SHIPPED
 *    TelegramTier against the fake wire instead of the stub.
 */
final class ColdCopyPublishDeleteTest extends TestCase
{
    private const ACCOUNT = 13;

    private const BUCKET = 'data:archive';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private int $channelId = 0;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'cold-publish');
        $this->index = new MemoryStoreIndex;
    }

    private function channel(): TelegramStoreChannel
    {
        return new TelegramStoreChannel(self::BUCKET, $this->api->apiMap());
    }

    /**
     * Resolve (creating if needed) the bucket's own channel and remember its
     * id, so fixtures can be posted INSIDE it — the fake's wire is
     * peer-partitioned, and a message left in another partition would be
     * invisible to this bucket's searches, which would make every assertion
     * here vacuous.
     */
    private function bucketChannelId(): int
    {
        if ($this->channelId === 0) {
            $this->channelId = (int) ($this->channel()->bucketInputPeer()['channel_id'] ?? 0);
        }

        return $this->channelId;
    }

    private function seedDocument(string $name, string $bytes): int
    {
        $this->api->seedPeerId = $this->bucketChannelId();

        return $this->api->uploadDocument($name, $bytes);
    }

    private function service(?ReferencePolicy $policy = null, ?RemoteTierContract $tier = null): StoreService
    {
        return new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 1024 * 1024),
            queue: new SyncUploadQueue,
            referencePolicy: $policy,
            tier: $tier,
        );
    }

    /** @return array<string, mixed> */
    private static function targetPeer(int $channelId = 900): array
    {
        return ['_' => 'inputPeerChannel', 'channel_id' => $channelId, 'access_hash' => 4242];
    }

    // ── publish by hash, with no local row ─────────────────────────────

    public function test_publish_by_reference_re_resolves_a_missing_media_row_from_the_remote_tier(): void
    {
        $msgId = $this->seedDocument('cold-1.jpg', 'jpeg bytes');
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative());

        self::assertNull($this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId), 'precondition: no local media row');

        $posted = $cold->publishByReference(self::BUCKET, $msgId, self::targetPeer(), 'from the cold tier');

        self::assertGreaterThan(0, $posted);
        self::assertSame(0, $this->api->uploadPartCalls, 'a hash-addressed publish never re-uploads, cold or not');
        self::assertCount(1, $this->api->referenceSends);
        self::assertSame('from the cold tier', $this->api->referenceSends[0]['caption']);
        $document = $this->api->referenceSends[0]['media']['id'];
        self::assertSame(str_starts_with((string) $document['file_reference'], 'ref-'), true);
        self::assertNotNull(
            $this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId),
            'the remote answer is cached, so the next publish is a local hit',
        );
    }

    public function test_the_retaining_posture_still_refuses_a_missing_media_row(): void
    {
        $msgId = $this->seedDocument('cold-2.jpg', 'jpeg bytes');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tf_store_media has no reference');
        try {
            $this->service(ReferencePolicy::retainLocal())->publishByReference(self::BUCKET, $msgId, self::targetPeer());
        } finally {
            self::assertSame(0, $this->api->uploadPartCalls, 'a missing row must never become an upload');
        }
    }

    public function test_a_cold_publish_of_a_message_that_holds_no_document_still_throws_and_never_uploads(): void
    {
        $this->api->seedPeerId = $this->bucketChannelId();
        $textMsgId = $this->api->sendText('just a caption, no document');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tf_store_media has no reference');
        try {
            $this->service(ReferencePolicy::coldRemoteAuthoritative())
                ->publishByReference(self::BUCKET, $textMsgId, self::targetPeer());
        } finally {
            self::assertSame(0, $this->api->uploadPartCalls);
            self::assertSame([], $this->api->referenceSends);
        }
    }

    /**
     * The seam, asserted as a seam: the wire is EMPTY here and the reference
     * still resolves, because the resolver was bound. If any part of the cold
     * branch were decided by what this host can reach, this test would be
     * unrunnable in CI — which is exactly the point of pinning it.
     */
    public function test_the_cold_branch_is_decided_by_the_injected_tier_and_not_by_the_wire(): void
    {
        $stub = new class implements RemoteTierContract
        {
            public int $manifestCalls = 0;

            public int $mediaCalls = 0;

            public int $listCalls = 0;

            public int $durableCalls = 0;

            public function manifestFor(TelegramStoreChannel $channel, string $name): ?array
            {
                $this->manifestCalls++;

                return null;
            }

            public function mediaFor(TelegramStoreChannel $channel, int $msgId): ?array
            {
                $this->mediaCalls++;

                return [
                    'reference' => MediaReference::fromIndexRow([
                        'doc_id' => 5150,
                        'access_hash' => 123456789,
                        'file_reference' => 'injected-cold-reference',
                        'size' => 12,
                    ]),
                    'peer' => ['_' => 'inputPeerChannel', 'channel_id' => 900, 'access_hash' => 4242],
                    'row' => [
                        'media_type' => 'document',
                        'doc_id' => 5150,
                        'access_hash' => 123456789,
                        'file_reference' => 'injected-cold-reference',
                        'size' => 12,
                        'mime' => 'image/jpeg',
                    ],
                ];
            }

            public function durableMediaFor(TelegramStoreChannel $channel, int $msgId): ?array
            {
                $this->durableCalls++;

                // The CONTENT half, deliberately unanswered. A durable answer
                // carries the sha256 of the bytes Telegram holds RIGHT NOW, and
                // the only way to get one is a full download this stub cannot
                // perform. Returning a mediaFor()-shaped row here would be the
                // exact false-green this interface was split to kill: it would
                // hand the reclaim path a proof nobody ever hashed. null is the
                // honest refusal, and the assertion below holds the line that
                // publishing never comes asking for it.
                return null;
            }

            public function listObjects(TelegramStoreChannel $channel, int $limit = 100): array
            {
                $this->listCalls++;

                return [];
            }
        };

        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative(), $stub);

        $posted = $cold->publishByReference(self::BUCKET, 4242, self::targetPeer(), 'bound seam');

        self::assertGreaterThan(0, $posted);
        self::assertSame(1, $stub->mediaCalls, 'the bound resolver decided the branch');
        self::assertSame(0, $stub->manifestCalls);
        self::assertSame(0, $stub->durableCalls, 'a publish is a read, and a read never buys a content hash');
        self::assertSame(0, $this->api->uploadPartCalls);
        self::assertSame('injected-cold-reference', $this->api->referenceSends[0]['media']['id']['file_reference']);
    }

    public function test_the_shipped_resolver_is_constructible_without_a_scope_or_a_disk(): void
    {
        // The default resolver must stay plain-PHP constructible: bin/standalone-smoke.php
        // is the gate for the whole module, and a resolver that needed a live
        // account to exist would put every cold path out of reach of it.
        self::assertInstanceOf(TelegramTier::class, new TelegramTier);
    }

    // ── revoke, with no local row ──────────────────────────────────────

    public function test_delete_revokes_a_cold_object_that_has_no_local_row(): void
    {
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative());
        $cold->put(self::BUCKET, 'gone.bin', 'bytes to forget');

        // Wipe the local truth table: the object exists only on the wire now.
        $this->index->deleteObject(self::ACCOUNT, self::BUCKET, 'gone.bin');
        self::assertNull($this->index->findObject(self::ACCOUNT, self::BUCKET, 'gone.bin'), 'precondition: no local row');
        self::assertTrue($cold->exists(self::BUCKET, 'gone.bin'), 'the cold tier still sees it');

        $cold->delete(self::BUCKET, 'gone.bin');

        self::assertNotSame([], $this->api->deletes, 'the revoke must actually be sent to the channel');
        $remaining = array_column($this->api->messages, 'name');
        self::assertNotContains('TFSTORE1: gone.bin', $remaining, 'the manifest message must be gone from the wire');
        self::assertFalse($cold->exists(self::BUCKET, 'gone.bin'), 'and the object must no longer exist');
    }

    public function test_delete_stays_a_no_op_for_a_retaining_posture_with_no_local_row(): void
    {
        $warm = $this->service(ReferencePolicy::retainLocal());
        $warm->put(self::BUCKET, 'kept.bin', 'still here');
        $this->index->deleteObject(self::ACCOUNT, self::BUCKET, 'kept.bin');
        $deletesBefore = count($this->api->deletes);

        $warm->delete(self::BUCKET, 'kept.bin');

        self::assertCount($deletesBefore, $this->api->deletes, 'no local row, no revoke: the default never invents a delete');
    }
}
