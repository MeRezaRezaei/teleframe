<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionTodoCompletionsCompleted;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionTodoCompletionsIncompleted;

/** Constructor model for messageActionTodoCompletions of MessageAction (crc32 cc7c5c89). */
final class TlMessageActionMessageActionTodoCompletions extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_todo_completions';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function completed(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionTodoCompletionsCompleted::class);
    }
    public function incompleted(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionTodoCompletionsIncompleted::class);
    }
}
