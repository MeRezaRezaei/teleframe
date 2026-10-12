<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.peerColors of help.PeerColors.
 */
final class TlHelpPeerColorsData extends TlHelpPeerColorsAbstractData
{
    public function __construct(
    public int $hash,
    public array $colors,
    ) {
    }
}
