<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageEntityStrike of MessageEntity.
 */
final class MessageEntityStrikeData extends TlMessageEntityAbstractData
{
    public function __construct(
    public int $offset,
    public int $length,
    ) {
    }
}
