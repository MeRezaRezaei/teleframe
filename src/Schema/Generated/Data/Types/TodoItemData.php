<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for todoItem of TodoItem.
 */
final class TodoItemData extends TlTodoItemAbstractData
{
    public function __construct(
    public int $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $title,
    ) {
    }
}
