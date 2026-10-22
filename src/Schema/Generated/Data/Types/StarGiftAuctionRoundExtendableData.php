<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAuctionRoundExtendable of StarGiftAuctionRound.
 */
final class StarGiftAuctionRoundExtendableData extends TlStarGiftAuctionRoundAbstractData
{
    public function __construct(
    public int $num,
    public int $duration,
    public int $extendTop,
    public int $extendWindow,
    ) {
    }
}
