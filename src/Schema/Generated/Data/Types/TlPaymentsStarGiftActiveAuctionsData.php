<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.starGiftActiveAuctions of payments.StarGiftActiveAuctions.
 */
final class TlPaymentsStarGiftActiveAuctionsData extends TlPaymentsStarGiftActiveAuctionsAbstractData
{
    public function __construct(
    public array $auctions,
    public array $users,
    public array $chats,
    ) {
    }
}
