<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftActiveAuctionState of StarGiftActiveAuctionState.
 */
final class StarGiftActiveAuctionStateData extends TlStarGiftActiveAuctionStateAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $gift,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAuctionStateAbstractData $state,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAuctionUserStateAbstractData $userState,
    ) {
    }
}
