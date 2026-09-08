<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.resaleStarGifts of payments.ResaleStarGifts.
 */
final class TlPaymentsResaleStarGiftsData extends TlPaymentsResaleStarGiftsAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public array $gifts,
    public ?string $nextOffset,
    public ?array $attributes,
    public ?int $attributesHash,
    public array $chats,
    public ?array $counters,
    public array $users,
    ) {
    }
}
