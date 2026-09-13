<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.starGiftAuctionAcquiredGifts of payments.StarGiftAuctionAcquiredGifts.
 */
final class TlPaymentsStarGiftAuctionAcquiredGiftsData extends TlPaymentsStarGiftAuctionAcquiredGiftsAbstractData
{
    public function __construct(
    public array $gifts,
    public array $users,
    public array $chats,
    ) {
    }
}
