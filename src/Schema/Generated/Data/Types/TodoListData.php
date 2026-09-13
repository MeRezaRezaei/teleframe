<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for todoList of TodoList.
 */
final class TodoListData extends TlTodoListAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'othersCanAppend' => ['flags', 0],
        'othersCanComplete' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $othersCanAppend,
    public ?bool $othersCanComplete,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $title,
    public array $list,
    ) {
    }
}
