<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoListTodoListList;

/** Constructor model for todoList of TodoList (crc32 49b92a26). */
final class TlTodoListTodoList extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_todo_list_todo_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'others_can_append' => 'bool',
        'others_can_complete' => 'bool',
    ];

    public function list(): HasMany
    {
        return $this->tlChild(TlTodoListTodoListList::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'title');
    }
}
