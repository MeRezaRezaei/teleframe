<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageEntitySpoiler of MessageEntity.
 */
final class MessageEntitySpoilerData extends TlMessageEntityAbstractData
{
    public function __construct(
    public int $offset,
    public int $length,
    ) {
    }
}
