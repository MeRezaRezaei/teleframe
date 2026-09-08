<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionTodoAppendTasksList;

/** Constructor model for messageActionTodoAppendTasks of MessageAction (crc32 c7edbc83). */
final class TlMessageActionMessageActionTodoAppendTasks extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_todo_append_tasks';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function list(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionTodoAppendTasksList::class);
    }
}
