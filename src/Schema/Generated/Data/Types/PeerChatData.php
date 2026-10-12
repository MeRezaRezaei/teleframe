<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for peerChat of Peer.
 */
final class PeerChatData extends TlPeerAbstractData
{
    public function __construct(
    public int $chatId,
    ) {
    }
}
