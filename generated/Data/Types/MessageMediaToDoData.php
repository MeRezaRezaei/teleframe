<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaToDo of MessageMedia.
 */
final class MessageMediaToDoData extends TlMessageMediaAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTodoListAbstractData $todo,
    public ?array $completions,
    ) {
    }
}
