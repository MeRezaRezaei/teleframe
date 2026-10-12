<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

/**
 * Canonical peer-shape normalizer — the wire→mirror bridge.
 *
 * The MTProto wire decodes a Peer as its constructor object:
 *   peerUser#59511722   user_id:long    = Peer
 *   peerChat#36c6019a   chat_id:long    = Peer
 *   peerChannel#a2a5371e channel_id:long = Peer
 * (see TL_telegram_v227.tl, lines 73-75).
 *
 * The NF5 mirror stores every Peer ref as an inline `_type TINYINT` +
 * `_id BIGINT` pair on the host row (spec section 6: "Peer-typed fields →
 * peer_type TINYINT + peer_id BIGINT ... 1=user 2=chat 3=channel"). The
 * enum follows the TL constructor order, so the mapping is mechanical:
 *
 *   peerUser    → 1
 *   peerChat    → 2
 *   peerChannel → 3
 *
 * Every consumer of a peer (MirrorFactDecomposer::fillPeerHalf, the
 * UpdateRouter, the SelfOriginatedClassifier) reads the canonical pair.
 * Without this bridge the raw wire object would be read as absent
 * (_type=0/_id=0) and the fact silently placed in the wrong path — the
 * exact "wrong path of ingesting" the FK-clue mechanism exists to catch.
 */
final class PeerShapeTool
{
    public const PEER_USER = 1;

    public const PEER_CHAT = 2;

    public const PEER_CHANNEL = 3;

    /**
     * Normalize a peer value to the canonical [_type, _id] pair.
     *
     * Accepts any of the shapes that can appear in a decoded payload:
     *  - wire ctor object:  ['_' => 'peerChannel', 'channel_id' => 123]
     *  - canonical pair:    ['_type' => 3, '_id' => 123]
     *  - bare pair:         ['type' => 3, 'id' => 123]
     *  - top-level channel: ['channel_id' => 123] (channel-specific updates)
     * Returns [0, 0] for anything it cannot normalize (never throws).
     *
     * @param  array<string, mixed>  $peer
     * @return array{0: int, 1: int} [type, id]
     */
    public static function normalize(array $peer): array
    {
        $ctor = (string) ($peer['_'] ?? '');
        if ($ctor !== '') {
            $wire = match ($ctor) {
                'peerUser' => [self::PEER_USER, 'user_id'],
                'peerChat' => [self::PEER_CHAT, 'chat_id'],
                'peerChannel' => [self::PEER_CHANNEL, 'channel_id'],
                default => null,
            };
            if ($wire !== null) {
                return [$wire[0], (int) ($peer[$wire[1]] ?? 0)];
            }
        }

        $type = (int) ($peer['_type'] ?? $peer['type'] ?? 0);
        if ($type !== 0) {
            $id = (int) ($peer['_id'] ?? $peer['id'] ?? 0);

            return [$type, $id];
        }

        $channelId = (int) ($peer['channel_id'] ?? 0);
        if ($channelId !== 0) {
            return [self::PEER_CHANNEL, $channelId];
        }

        return [0, 0];
    }
}
