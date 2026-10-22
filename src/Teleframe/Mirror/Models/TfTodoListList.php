<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 child of tf_todo_lists — the 1:N Vector<TodoItem> list
 * fact, keyed by the composite (account_id, todo_list_id) parent key.
 * TodoItem union (single ctor) flattened to scalar id + title text
 * (title entities are deferred).
 *
 * @property int $account_id
 * @property int $todo_list_id
 * @property int $position
 * @property int $id
 * @property string $title
 */
final class TfTodoListList extends MirrorChildModel
{
    protected $table = 'tf_todo_lists_list';

    /** @var list<string> */
    protected $fillable = ['account_id', 'todo_list_id', 'position', 'id', 'title'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'todo_list_id' => 'int', 'position' => 'int', 'id' => 'int'];
}
