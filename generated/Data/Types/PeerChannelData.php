<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for peerChannel of Peer.
 */
final class PeerChannelData extends TlPeerAbstractData
{
    public function __construct(
    public int $channelId,
    ) {
    }
}
