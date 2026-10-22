<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for peerSelfLocated of PeerLocated.
 */
final class PeerSelfLocatedData extends TlPeerLocatedAbstractData
{
    public function __construct(
    public int $expires,
    ) {
    }
}
