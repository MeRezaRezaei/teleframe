<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Eloquent;

use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use PHPUnit\Framework\TestCase;

/**
 * PeerShapeTool — the wire→canonical peer bridge.
 *
 * Owner verbatim 2026-09-14: "supose we have the telgram data came from
 * its mtproto and they simple shoould be insertable into the fully nf 5
 * fully migrated realational database and what ever forign key that fails
 * gives us a clue to the wrong path of ingesting".
 *
 * The wire decodes Peer as its constructor object (peerUser#user_id,
 * peerChat#chat_id, peerChannel#channel_id); the NF5 mirror stores the
 * inline _type/_id pair (spec enum 1=user 2=chat 3=channel). This tool
 * normalizes any of the shapes a decoded payload can carry into the
 * canonical pair — without it, real wire peers silently become 0/0 and
 * the fact lands in the wrong ingest path (exactly what the FK-clue
 * mechanism exists to surface).
 */
final class PeerShapeToolTest extends TestCase
{
    public function test_wire_user_ctor_normalizes_to_user_peer(): void
    {
        self::assertSame(
            [PeerShapeTool::PEER_USER, 123],
            PeerShapeTool::normalize(['_' => 'peerUser', 'user_id' => 123]),
        );
    }

    public function test_wire_chat_ctor_normalizes_to_chat_peer(): void
    {
        self::assertSame(
            [PeerShapeTool::PEER_CHAT, 456],
            PeerShapeTool::normalize(['_' => 'peerChat', 'chat_id' => 456]),
        );
    }

    public function test_wire_channel_ctor_normalizes_to_channel_peer(): void
    {
        self::assertSame(
            [PeerShapeTool::PEER_CHANNEL, 789],
            PeerShapeTool::normalize(['_' => 'peerChannel', 'channel_id' => 789]),
        );
    }

    public function test_canonical_pair_passes_through_unchanged(): void
    {
        self::assertSame(
            [2, 900],
            PeerShapeTool::normalize(['_type' => 2, '_id' => 900]),
        );
    }

    public function test_bare_pair_is_also_accepted(): void
    {
        self::assertSame(
            [3, 55],
            PeerShapeTool::normalize(['type' => 3, 'id' => 55]),
        );
    }

    public function test_top_level_channel_id_is_a_channel_peer(): void
    {
        self::assertSame(
            [PeerShapeTool::PEER_CHANNEL, 55],
            PeerShapeTool::normalize(['channel_id' => 55]),
        );
    }

    public function test_unknown_peer_is_0_0_not_a_throw(): void
    {
        self::assertSame([0, 0], PeerShapeTool::normalize(['_' => 'inputPeerUser']));
        self::assertSame([0, 0], PeerShapeTool::normalize([]));
        self::assertSame([0, 0], PeerShapeTool::normalize(['channel_id' => 0]));
    }
}
