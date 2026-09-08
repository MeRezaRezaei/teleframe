<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.peerColorProfileSet of help.PeerColorSet.
 */
final class TlHelpPeerColorProfileSetData extends TlHelpPeerColorSetAbstractData
{
    public function __construct(
    public array $paletteColors,
    public array $bgColors,
    public array $storyColors,
    ) {
    }
}
