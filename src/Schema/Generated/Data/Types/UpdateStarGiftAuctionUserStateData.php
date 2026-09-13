<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateStarGiftAuctionUserState of Update.
 */
final class UpdateStarGiftAuctionUserStateData extends TlUpdateAbstractData
{
    public function __construct(
    public int $giftId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAuctionUserStateAbstractData $userState,
    ) {
    }
}
