<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for outboxReadDate of OutboxReadDate.
 */
final class OutboxReadDateData extends TlOutboxReadDateAbstractData
{
    public function __construct(
    public int $date,
    ) {
    }
}
