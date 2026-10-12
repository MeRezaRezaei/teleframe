<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_todo_lists`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 2.
 */
final class TfTodoLists extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_todo_lists';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, todo_list_id),
     * which the database enforces; `todo_list_id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'todo_list_id';

    public $timestamps = false;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'account_id' => 'int',
        'todo_list_id' => 'int',
        'others_can_append' => 'boolean',
        'others_can_complete' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_todo_lists_list`(account_id, todo_list_id) -> `tf_todo_lists`(account_id, todo_list_id), ON DELETE CASCADE. Keyed on `todo_list_id` = `todo_list_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function todoListsList(): HasMany
    {
        return $this->hasMany(TfTodoListsList::class, 'todo_list_id', 'todo_list_id');
    }

    /** Declared foreign key (1:N): `tf_todo_lists_title`(account_id, todo_list_id) -> `tf_todo_lists`(account_id, todo_list_id), ON DELETE CASCADE. Keyed on `todo_list_id` = `todo_list_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function todoListsTitle(): HasMany
    {
        return $this->hasMany(TfTodoListsTitle::class, 'todo_list_id', 'todo_list_id');
    }
}
