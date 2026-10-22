<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for todoCompletion of TodoCompletion.
 */
final class TodoCompletionData extends TlTodoCompletionAbstractData
{
    public function __construct(
    public int $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $completedBy,
    public int $date,
    ) {
    }
}
