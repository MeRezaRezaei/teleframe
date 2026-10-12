<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateBotPurchasedPaidMedia of Update.
 */
final class UpdateBotPurchasedPaidMediaData extends TlUpdateAbstractData
{
    public function __construct(
    public int $userId,
    public string $payload,
    public int $qts,
    ) {
    }
}
