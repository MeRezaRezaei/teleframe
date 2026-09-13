<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageEntityBotCommand of MessageEntity.
 */
final class MessageEntityBotCommandData extends TlMessageEntityAbstractData
{
    public function __construct(
    public int $offset,
    public int $length,
    ) {
    }
}
