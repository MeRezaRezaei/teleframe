<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\PeerTarget;
use MeRezaRezaei\Teleframe\Store\Backup\PeerTargetRegistry;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use PHPUnit\Framework\TestCase;

/**
 * PeerTargetRegistry (2026-09-24 backup-store remainder design §2/§6,
 * cycle 11 wave 2) — config target rows → an immutable list of PeerTarget
 * value objects, plus the dialog-walk classification seam (channel vs
 * supergroup).
 *
 * Coverage: snake+camel key mapping ({peer_id|peerId, access_hash|
 * accessHash, peer_type|peerType}), byBucket() first-match/null, LOUD
 * config-error ruling (no bucket / no peer id / non-array row / invalid
 * mode / non-array members → BackupException, never a silent drop),
 * classifyPeerType via the injected callable AND the built-in zero-regex
 * path (megagroup-flagged channel → 'group', broadcast channel →
 * 'channel', unknown type → 'channel' default), and requiresSeal() =
 * group || sealed (design §6: member/public targets REQUIRE seal).
 *
 * Pure data — zero container, zero regex.
 */
final class PeerTargetRegistryTest extends TestCase
{
    public function test_snake_key_rows_map_to_targets(): void
    {
        $registry = new PeerTargetRegistry([
            ['bucket' => 'nightly', 'peer_id' => 123, 'access_hash' => 456789],
            ['bucket' => 'weekly', 'peer_id' => '77', 'peer_type' => 'group', 'mode' => 'sealed', 'passphrase' => 'p', 'members' => [1, 'alice']],
        ]);

        $targets = $registry->targets();

        self::assertCount(2, $targets);
        self::assertSame('nightly', $targets[0]->bucket);
        self::assertSame(123, $targets[0]->peerId);
        self::assertSame(456789, $targets[0]->accessHash);
        self::assertSame('channel', $targets[0]->peerType);
        self::assertSame(ObjectManifest::MODE_SEALED, $targets[0]->mode);
        self::assertNull($targets[0]->passphrase);
        self::assertSame([], $targets[0]->memberAccounts);

        self::assertSame('group', $targets[1]->peerType);
        self::assertSame('p', $targets[1]->passphrase);
        self::assertSame([1, 'alice'], $targets[1]->memberAccounts);
    }

    public function test_camel_key_rows_and_numeric_string_hashes_map_too(): void
    {
        $registry = new PeerTargetRegistry([
            ['bucket' => 'c', 'peerId' => 55, 'accessHash' => '77', 'peerType' => 'group'],
        ]);

        $targets = $registry->targets();

        self::assertCount(1, $targets);
        self::assertSame(55, $targets[0]->peerId);
        // Numeric string hashes normalize to int (PeerTargetRegistry
        // ParseRule: is_numeric strings cast).
        self::assertSame(77, $targets[0]->accessHash);
        self::assertSame('group', $targets[0]->peerType);
    }

    public function test_by_bucket_returns_first_match_and_null_for_unknown(): void
    {
        $registry = new PeerTargetRegistry([
            ['bucket' => 'a', 'peer_id' => 1],
            ['bucket' => 'b', 'peer_id' => 2],
        ]);

        self::assertSame(2, $registry->byBucket('b')?->peerId);
        self::assertSame(1, $registry->byBucket('a')?->peerId);
        self::assertNull($registry->byBucket('missing'));
    }

    public function test_row_without_bucket_throws_loud(): void
    {
        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('has no bucket');

        (new PeerTargetRegistry([['peer_id' => 1]]))->targets();
    }

    public function test_row_without_peer_id_throws_loud(): void
    {
        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('has no usable peer id');

        (new PeerTargetRegistry([['bucket' => 'b']]))->targets();
    }

    public function test_non_array_row_throws_loud(): void
    {
        $this->expectException(BackupException::class);
        $this->expectExceptionMessage('is not an array');

        (new PeerTargetRegistry(['garbage']))->targets();
    }

    public function test_invalid_mode_and_non_array_members_throw_loud(): void
    {
        try {
            (new PeerTargetRegistry([['bucket' => 'b', 'peer_id' => 1, 'mode' => 'plaintext']]))->targets();
            self::fail('expected BackupException for an invalid mode');
        } catch (BackupException $e) {
            self::assertStringContainsString('invalid mode', $e->getMessage());
        }

        try {
            (new PeerTargetRegistry([['bucket' => 'b', 'peer_id' => 1, 'members' => 'alice']]))->targets();
            self::fail('expected BackupException for non-array members');
        } catch (BackupException $e) {
            self::assertStringContainsString('non-array members', $e->getMessage());
        }
    }

    public function test_classify_peer_type_via_the_injected_callable(): void
    {
        $registry = new PeerTargetRegistry([], static fn (array $chat): string => $chat['kind'] ?? 'channel');

        self::assertSame('group', $registry->classifyPeerType(['kind' => 'group']));
        self::assertSame('channel', $registry->classifyPeerType(['kind' => 'channel']));
        self::assertSame('channel', $registry->classifyPeerType([]));
    }

    public function test_built_in_classify_megagroup_to_group_broadcast_to_channel_unknown_defaults_channel(): void
    {
        $registry = new PeerTargetRegistry;

        // Both arrive as {_:'channel'} chat objects; the megagroup flag
        // separates the supergroup from the broadcast channel (design §6).
        self::assertSame('group', $registry->classifyPeerType(['_' => 'channel', 'megagroup' => true]));
        self::assertSame('channel', $registry->classifyPeerType(['_' => 'channel']));
        self::assertSame('channel', $registry->classifyPeerType(['_' => 'broadcast']));
        self::assertSame('channel', $registry->classifyPeerType(['_' => 'chat']));
        self::assertSame('channel', $registry->classifyPeerType([]));
    }

    public function test_requires_seal_group_or_sealed_mode(): void
    {
        // group + raw → REQUIRED ("member/public targets require seal").
        self::assertTrue((new PeerTarget('b', 1, peerType: 'group', mode: ObjectManifest::MODE_RAW))->requiresSeal());
        // Owned channel with the sealed default → REQUIRED.
        self::assertTrue((new PeerTarget('b', 1, peerType: 'channel', mode: ObjectManifest::MODE_SEALED))->requiresSeal());
        // Owned channel + raw → NOT required (only groups force seal).
        self::assertFalse((new PeerTarget('b', 1, peerType: 'channel', mode: ObjectManifest::MODE_RAW))->requiresSeal());
        // Default ctor mode is sealed → a default target requires seal.
        self::assertTrue((new PeerTarget('b', 1))->requiresSeal());
    }

    public function test_input_peer_is_always_input_peer_channel_with_the_unknown_hash_sentinel(): void
    {
        // Null access hash renders as 0 — the MTProto "unknown hash"
        // sentinel, VALID for invitation/join flows (PeerTarget.php
        // RULING); a known hash passes through.
        self::assertSame(
            ['_' => 'inputPeerChannel', 'channel_id' => 123, 'access_hash' => 0],
            (new PeerTarget('b', 123))->inputPeer(),
        );
        self::assertSame(
            ['_' => 'inputPeerChannel', 'channel_id' => 123, 'access_hash' => 456],
            (new PeerTarget('b', 123, 456))->inputPeer(),
        );
    }

    public function test_to_row_never_carries_the_passphrase(): void
    {
        $row = (new PeerTarget('b', 1, 2, 'group', ObjectManifest::MODE_SEALED, 's3cret', [9]))->toRow();

        self::assertSame('b', $row['bucket']);
        self::assertSame(1, $row['peer_id']);
        self::assertSame('group', $row['peer_type']);
        self::assertTrue($row['has_passphrase']);
        self::assertArrayNotHasKey('passphrase', $row);
    }

    public function test_target_is_a_value_object_constructible_plain_php(): void
    {
        // PeerTarget itself is pure data — the registry maps onto it.
        self::assertInstanceOf(PeerTarget::class, new PeerTarget('b', 1));
    }
}
