<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaTodo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaToDo;

/** Anchor model for TL type TodoList (spec §4.1). */
final class TlTodoList extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_todo_list_todo_list';

    protected $guarded = [];

    public function todo(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaTodo::class, 'todo');
    }
    public function todoMessageMediaToDo(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaToDo::class, 'todo');
    }
}
