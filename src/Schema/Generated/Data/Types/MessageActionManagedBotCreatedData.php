<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionManagedBotCreated of MessageAction.
 */
final class MessageActionManagedBotCreatedData extends TlMessageActionAbstractData
{
    public function __construct(
    public int $botId,
    ) {
    }
}
