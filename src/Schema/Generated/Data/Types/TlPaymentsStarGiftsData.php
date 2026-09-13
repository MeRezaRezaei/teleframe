<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.starGifts of payments.StarGifts.
 */
final class TlPaymentsStarGiftsData extends TlPaymentsStarGiftsAbstractData
{
    public function __construct(
    public int $hash,
    public array $gifts,
    public array $chats,
    public array $users,
    ) {
    }
}
