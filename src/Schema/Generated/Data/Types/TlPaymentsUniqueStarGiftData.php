<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.uniqueStarGift of payments.UniqueStarGift.
 */
final class TlPaymentsUniqueStarGiftData extends TlPaymentsUniqueStarGiftAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarGiftAbstractData $gift,
    public array $chats,
    public array $users,
    ) {
    }
}
