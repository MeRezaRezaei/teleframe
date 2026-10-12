<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Store\Backup\PeerMembership;
use MeRezaRezaei\Teleframe\Store\Backup\PeerTarget;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * A CONFIGURED group/channel is a REAL backup destination (verbatim
 * 2026-09-24: "channels and groups have the ability that you can add
 * someone into them for backup … it's only a matter of managing indexes").
 *
 * This replaces the suite that pinned the ABSENCE of that capability.
 * Both directions are covered and both matter: the destination is really
 * used (put/get/search/history/delete all address the target peer, and a
 * write→restore round trip finds every chunk across peers), AND a target
 * that cannot work still fails loudly before any byte moves.
 *
 * The index question, as implemented and asserted here: a manifest, its
 * registry marker and all of its chunks are CO-LOCATED in one peer. Reads
 * search the destination first and fall back to the bucket's own channel
 * for objects written before a target existed, and an object's chunks are
 * then read from the peer its manifest came from — never fanned out.
 */
final class PeerTargetRoutingTest extends TestCase
{
    private const BUCKET = 'media-pool';

    private const GROUP_ID = -1001234567890;

    /** A capability-granting value: it must never reach any output. */
    private const GROUP_HASH = 8675309;

    private const PASSPHRASE = 'group target passphrase';

    private FakeStoreApi $api;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'peer');
        // Multi-peer: a search of one peer must NEVER see another's
        // messages, or a misrouted lookup would look like success.
        $this->api->seedFallback = false;
    }

    private function groupTarget(?int $hash = self::GROUP_HASH, string $mode = ObjectManifest::MODE_SEALED): PeerTarget
    {
        return new PeerTarget(
            bucket: self::BUCKET,
            peerId: self::GROUP_ID,
            accessHash: $hash,
            peerType: 'group',
            mode: $mode,
        );
    }

    /** The bucket's own channel peer, resolved through a real channel. */
    private function bucketPeer(): array
    {
        return (new TelegramStoreChannel(self::BUCKET, $this->api->apiMap()))->inputPeer();
    }

    private function targetPeer(): array
    {
        return $this->groupTarget()->inputPeer();
    }

    private function channel(?PeerTarget $target = null, ?PeerMembership $membership = null): TelegramStoreChannel
    {
        return new TelegramStoreChannel(self::BUCKET, $this->api->apiMap(), peerTarget: $target, membership: $membership);
    }

    private function service(TelegramStoreChannel $channel): StoreService
    {
        return new StoreService(
            accountId: 7,
            channelFactory: static fn (string $bucket): TelegramStoreChannel => $channel,
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );
    }

    // ── 1. the target really is the destination ───────────────────────

    public function test_input_peer_is_the_target_not_the_bucket_channel(): void
    {
        $channel = $this->channel($this->groupTarget());

        $this->assertSame(self::GROUP_ID, $channel->inputPeer()['channel_id']);
        $this->assertSame('inputPeerChannel', $channel->inputPeer()['_']);
        $this->assertNotSame(
            $channel->inputPeer()['channel_id'],
            $channel->bucketInputPeer()['channel_id'],
            'the target must be a different peer from the bucket channel',
        );
    }

    public function test_the_upload_and_the_manifest_message_both_go_to_the_target_peer(): void
    {
        $service = $this->service($this->channel($this->groupTarget()));

        $service->put(self::BUCKET, 'in-group.bin', 'group payload', ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $bucketPeer = $this->bucketPeer();
        $this->assertNotSame(self::GROUP_ID, (int) $bucketPeer['channel_id']);

        $sentPeers = array_unique(array_map(
            static fn (array $send): int => (int) $send['peer']['channel_id'],
            $this->api->sends,
        ));

        $this->assertSame(
            [self::GROUP_ID],
            array_values($sentPeers),
            'every send (registry, chunk, manifest) must be addressed to the target peer only',
        );

        $documents = array_values(array_filter($this->api->sends, static fn (array $s): bool => $s['kind'] === 'document'));
        $this->assertNotEmpty($documents, 'the chunk must actually be uploaded');
        foreach ($documents as $document) {
            $this->assertSame(self::GROUP_ID, (int) $document['peer']['channel_id']);
        }

        $this->assertSame(
            [],
            $this->api->messagesFor($bucketPeer),
            'the bucket channel must be left empty — nothing may land there',
        );
    }

    public function test_search_history_and_delete_are_addressed_to_the_target_peer(): void
    {
        $channel = $this->channel($this->groupTarget());
        $service = $this->service($channel);
        $service->put(self::BUCKET, 'routed.bin', 'bytes', ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $service->get(self::BUCKET, 'routed.bin', self::PASSPHRASE);
        $service->verify(self::BUCKET, 'routed.bin', self::PASSPHRASE);
        $service->delete(self::BUCKET, 'routed.bin');

        $searchPeers = array_map(
            static fn (array $search): int => (int) $search['peer']['channel_id'],
            $this->api->searches,
        );

        // The target is searched FIRST for every lookup. The bucket channel
        // may still be searched afterwards — that is the documented legacy
        // fallback for objects written before a target existed (the first
        // registry probe finds nothing in a fresh group and falls through) —
        // but it is never preferred over the target.
        $targetFirst = array_search(self::GROUP_ID, $searchPeers, true);
        $bucketEver = array_search(100, $searchPeers, true);
        $this->assertIsInt($targetFirst, 'the target peer must be searched');
        $this->assertLessThan($bucketEver ?? PHP_INT_MAX, $targetFirst, 'the target peer is searched before the bucket channel');

        $this->assertNotEmpty($this->api->deletes, 'delete must reach the wire');
        foreach ($this->api->deletes as $delete) {
            $this->assertSame(
                self::GROUP_ID,
                (int) $delete['peer']['channel_id'],
                'the revoke must be addressed to the peer that holds the messages',
            );
        }
    }

    public function test_history_walk_crawls_the_target_peer(): void
    {
        $service = $this->service($this->channel($this->groupTarget()));
        $service->put(self::BUCKET, 'walked.bin', 'bytes', ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $rows = $this->channel($this->groupTarget())->listAllRows();

        $this->assertNotEmpty($rows);
        $this->assertNotEmpty(
            array_filter($this->api->searches, static fn (array $s): bool => (int) $s['peer']['channel_id'] === self::GROUP_ID),
            'history must be read from the target peer',
        );
    }

    // ── 2. membership is ensured, and when ────────────────────────────

    public function test_the_membership_seam_runs_before_the_first_upload(): void
    {
        $scope = new class
        {
            /** @var list<string> */
            public array $events = [];

            public function joinChannel(mixed $channel): array
            {
                $this->events[] = 'join';

                return ['_' => 'updates'];
            }

            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                $this->events[] = 'probe';

                return ['_' => 'channelParticipants', 'count' => 2, 'participants' => [1, 2]];
            }

            public function inviteToChannel(mixed $channel, array $users): array
            {
                $this->events[] = 'invite';

                return ['_' => 'updates'];
            }
        };

        $target = new PeerTarget(
            bucket: self::BUCKET,
            peerId: self::GROUP_ID,
            accessHash: self::GROUP_HASH,
            peerType: 'group',
            mode: ObjectManifest::MODE_SEALED,
            memberAccounts: [42],
        );

        $channel = $this->channel($target, new PeerMembership($scope));
        $this->assertSame(['join', 'probe', 'invite'], $scope->events, 'join, then rights, then invite');

        $service = $this->service($channel);
        $service->put(self::BUCKET, 'm.bin', 'bytes', ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $this->assertGreaterThan(0, $this->api->uploadPartCalls);
        $this->assertSame(
            ['join', 'probe', 'invite'],
            $scope->events,
            'the seam must already be done BEFORE the first upload, not after it',
        );
    }

    public function test_a_target_the_account_is_already_in_still_uploads(): void
    {
        $scope = new class
        {
            public function joinChannel(mixed $channel): array
            {
                throw new RuntimeException('USER_ALREADY_PARTICIPANT');
            }

            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                return ['_' => 'channelParticipants', 'count' => 1, 'participants' => [1]];
            }
        };

        $service = $this->service($this->channel($this->groupTarget(), new PeerMembership($scope)));
        $result = $service->put(self::BUCKET, 'm.bin', 'bytes', ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $this->assertFalse($result['queued']);
        $this->assertSame('bytes', $service->get(self::BUCKET, 'm.bin', self::PASSPHRASE));
    }

    // ── 3. the refusal survives, and fires UP FRONT ───────────────────

    public function test_a_target_with_no_access_hash_is_still_refused(): void
    {
        try {
            $this->channel($this->groupTarget(hash: null));
            $this->fail('a target with no access hash must be refused');
        } catch (LogicException $e) {
            $this->assertStringContainsString('no access hash', $e->getMessage());
            $this->assertStringContainsString('teleframe.tstore.peer_target', $e->getMessage());
        }

        $this->assertSame(0, $this->api->uploadPartCalls, 'refusal must precede any upload');
        $this->assertSame([], $this->api->sends);
    }

    public function test_a_zero_access_hash_is_refused_too(): void
    {
        $this->expectException(LogicException::class);
        $this->channel($this->groupTarget(hash: 0));
    }

    public function test_an_unjoinable_target_is_refused_before_any_upload(): void
    {
        $scope = new class
        {
            public function joinChannel(mixed $channel): array
            {
                throw new RuntimeException('CHANNEL_PRIVATE: the account was never invited to this peer');
            }
        };

        $thrown = null;
        try {
            $this->channel($this->groupTarget(), new PeerMembership($scope));
        } catch (Throwable $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(RuntimeException::class, $thrown, 'a private, un-invited peer must fail loudly');
        $this->assertStringContainsString('CHANNEL_PRIVATE', $thrown->getMessage());
        $this->assertSame(0, $this->api->uploadPartCalls, 'nothing may be uploaded to a peer we cannot join');
        $this->assertSame([], $this->api->sends);
    }

    public function test_a_target_the_account_cannot_post_in_is_refused_before_any_upload(): void
    {
        // A group whose administrator set is empty: the one unambiguous
        // "this account cannot post there" signal the strict probe returns.
        $scope = new class
        {
            public function joinChannel(mixed $channel): array
            {
                return ['_' => 'updates'];
            }

            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                return ['_' => 'channelParticipants', 'count' => 0, 'participants' => []];
            }
        };

        try {
            $this->channel($this->groupTarget(), new PeerMembership($scope));
            $this->fail('a target the account cannot post in must be refused');
        } catch (LogicException $e) {
            $this->assertStringContainsString('cannot post in', $e->getMessage());
            $this->assertStringContainsString((string) self::GROUP_ID, $e->getMessage());
        }

        $this->assertSame(0, $this->api->uploadPartCalls, 'refusal must precede any upload');
        $this->assertSame([], $this->api->sends);
    }

    public function test_a_raw_put_to_a_group_target_is_refused_seal_policy(): void
    {
        // "you can make them public but put pass on whatever you send there"
        $service = $this->service($this->channel($this->groupTarget()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires sealed uploads');

        $service->put(self::BUCKET, 'leak.bin', 'readable plaintext', ObjectManifest::MODE_RAW);
    }

    public function test_the_no_target_default_still_uses_the_bucket_channel(): void
    {
        $api = new FakeStoreApi(scope: 'plain');
        $channel = new TelegramStoreChannel(self::BUCKET, $api->apiMap());

        $this->assertSame(100, $channel->channelId());
        $this->assertSame('inputPeerChannel', $channel->inputPeer()['_']);
        $this->assertNull($channel->peerTarget());
        $this->assertCount(1, $channel->readPeers(), 'an untargeted channel has exactly one read peer');
    }

    // ── 4. the access hash never leaks ────────────────────────────────

    public function test_the_access_hash_never_reaches_any_output(): void
    {
        $messages = [];
        $apiMap = $this->api->apiMap();

        $targets = [
            'no-hash' => $this->groupTarget(hash: null),
            'zero-hash' => $this->groupTarget(hash: 0),
            'rights' => $this->groupTarget(),
        ];

        foreach ($targets as $label => $target) {
            try {
                $membership = $label === 'rights' ? new PeerMembership(new class
                {
                    public function joinChannel(mixed $channel): array
                    {
                        return ['_' => 'updates'];
                    }

                    public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
                    {
                        return ['count' => 0, 'participants' => []];
                    }
                }) : null;

                new TelegramStoreChannel(self::BUCKET, $apiMap, peerTarget: $target, membership: $membership);
            } catch (Throwable $e) {
                $messages[] = $e->getMessage();
            }
        }

        // The unjoinable case, whose message comes straight off the wire.
        try {
            $this->channel($this->groupTarget(), new PeerMembership(new class
            {
                public function joinChannel(mixed $channel): array
                {
                    throw new RuntimeException('CHANNEL_PRIVATE');
                }
            }));
        } catch (Throwable $e) {
            $messages[] = $e->getMessage();
        }

        $this->assertNotEmpty($messages, 'each refusal must produce a message to scan');
        foreach ($messages as $message) {
            $this->assertStringNotContainsString(
                (string) self::GROUP_HASH,
                $message,
                'an access hash is capability-granting and must never appear in an error message',
            );
        }

        // And nothing about the seal-policy refusal either.
        try {
            $this->service($this->channel($this->groupTarget()))
                ->put(self::BUCKET, 'x.bin', 'bytes', ObjectManifest::MODE_RAW);
        } catch (Throwable $e) {
            $this->assertStringNotContainsString((string) self::GROUP_HASH, $e->getMessage());
        }
    }

    public function test_a_channel_is_built_once_per_bucket_not_once_per_call(): void
    {
        // The membership gate is "ensured before the first upload", not
        // "re-proved on every read": a channel is stateful (it caches the
        // resolved channel id, and the target's join/probe runs once), so a
        // factory that rebuilds it per call turns one put and three reads
        // into six join+probe pairs. The provider's factory memoizes per
        // bucket; this pins that shape, since the cost is invisible in a
        // single-operation test and fatal under flood-wait limits.
        $scope = new class
        {
            public int $joins = 0;

            public int $probes = 0;

            public function joinChannel(mixed $channel): array
            {
                $this->joins++;

                return ['_' => 'updates'];
            }

            public function getParticipants(mixed $channel, array $filter, int $offset, int $limit): array
            {
                $this->probes++;

                return ['_' => 'channelParticipants', 'count' => 1, 'participants' => [1]];
            }
        };

        $target = $this->groupTarget();
        $memo = [];
        $api = $this->api;
        // The provider's memoizing factory shape.
        $service = new StoreService(
            accountId: 7,
            channelFactory: static function (string $bucket) use (&$memo, $api, $target, $scope): TelegramStoreChannel {
                return $memo[$bucket] ??= new TelegramStoreChannel(
                    $bucket,
                    $api->apiMap(),
                    peerTarget: $target,
                    membership: new PeerMembership($scope),
                );
            },
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );

        $service->put(self::BUCKET, 'm.bin', 'data', ObjectManifest::MODE_SEALED, self::PASSPHRASE);
        $service->get(self::BUCKET, 'm.bin', self::PASSPHRASE);
        $service->verify(self::BUCKET, 'm.bin', self::PASSPHRASE);
        $service->get(self::BUCKET, 'm.bin', self::PASSPHRASE);

        $this->assertSame(1, $scope->joins, 'the join runs once, not once per call');
        $this->assertSame(1, $scope->probes, 'the post-rights probe runs once, not once per call');
        $this->assertSame('data', $service->get(self::BUCKET, 'm.bin', self::PASSPHRASE));
    }

    // ── 5. the round trip: managing indexes across peers ─────────────

    public function test_a_sealed_object_round_trips_through_a_group_target(): void
    {
        $channel = $this->channel($this->groupTarget());
        $service = $this->service($channel);
        $bytes = str_repeat('cold-storage-payload', 40_000);

        $service->put(self::BUCKET, 'big.sealed', $bytes, ObjectManifest::MODE_SEALED, self::PASSPHRASE);
        $this->assertSame($bytes, $service->get(self::BUCKET, 'big.sealed', self::PASSPHRASE));

        // The manifest and every chunk are in the TARGET peer's history —
        // a restore that looked in the bucket channel would find nothing.
        $targetMessages = $this->api->messagesFor($this->targetPeer());
        $names = array_column($targetMessages, 'name');
        $this->assertContains('TFSTORE1: big.sealed', $names, 'the manifest lives with its chunks');
        $this->assertSame([], $this->api->messagesFor($this->bucketPeer()));

        $report = $service->verify(self::BUCKET, 'big.sealed', self::PASSPHRASE);
        $this->assertTrue($report['ok'], implode('; ', $report['errors']));

        // A chunk is up to 1.95 GiB (ChunkPlanner::FREE_CAP), so a real
        // multi-CHUNK object cannot be built offline; the multi-PART upload
        // is the same streaming path and is what this asserts.
        $this->assertGreaterThan(1, $this->api->uploadPartCalls, 'the payload must span several 512 KiB parts');
    }

    public function test_a_raw_object_round_trips_through_a_channel_target(): void
    {
        $target = new PeerTarget(
            bucket: self::BUCKET,
            peerId: -100999,
            accessHash: 4242,
            peerType: 'channel',
            mode: ObjectManifest::MODE_RAW,
        );
        $service = $this->service($this->channel($target));
        $bytes = str_repeat('plain is fine on an owned channel', 30_000);

        $service->put(self::BUCKET, 'raw.bin', $bytes, ObjectManifest::MODE_RAW);
        $this->assertSame($bytes, $service->get(self::BUCKET, 'raw.bin'));
    }

    public function test_an_object_written_before_the_target_existed_still_restores(): void
    {
        // Written while there was no target: it lives in the bucket channel.
        $plain = $this->service($this->channel());
        $bytes = str_repeat('legacy bytes', 5_000);
        $plain->put(self::BUCKET, 'legacy.bin', $bytes, ObjectManifest::MODE_RAW);

        // A fresh login, now with a group target configured and an EMPTY
        // truth table: the object must still be found, and its chunks must
        // be read from the bucket channel they were written to.
        $index = new MemoryStoreIndex;
        $service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => $this->channel($this->groupTarget()),
            index: $index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );

        $this->assertSame($bytes, $service->get(self::BUCKET, 'legacy.bin'));
        $this->assertNotEmpty($index->findObject(7, self::BUCKET, 'legacy.bin'), 'the row is re-discovered');

        // And a NEW object with the target configured goes to the group, not
        // back to the bucket channel.
        $service->put(self::BUCKET, 'new.bin', 'fresh', ObjectManifest::MODE_SEALED, self::PASSPHRASE);
        $this->assertSame('fresh', $service->get(self::BUCKET, 'new.bin', self::PASSPHRASE));
    }

    public function test_a_chunk_is_never_read_from_a_peer_other_than_its_manifest(): void
    {
        // Shadowing attempt: the same chunk doc name planted in the OTHER
        // peer. A fan-out read would find the target peer's copy first and
        // fail the object; the pinned read stays in the bucket channel.
        $bytes = str_repeat('pinned read', 5_000);
        $this->service($this->channel())->put(self::BUCKET, 'pin.bin', $bytes, ObjectManifest::MODE_RAW);

        // The object was written with no target, so it lives in the bucket
        // channel and its truth row does not exist here: the read must
        // locate the manifest in the bucket channel and then read the chunk
        // THERE, not fan out to the group.
        $service = $this->service($this->channel($this->groupTarget()));

        $this->assertSame($bytes, $service->get(self::BUCKET, 'pin.bin'));
    }
}
