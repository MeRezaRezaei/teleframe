<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 child of tf_todo_lists — the FK→TextWithEntities title
 * fact, keyed by the composite (account_id, todo_list_id) parent key.
 * Flattened to the scalar text (entities are deferred).
 *
 * @property int $account_id
 * @property int $todo_list_id
 * @property string $title
 */
final class TfTodoListTitle extends MirrorChildModel
{
    protected $table = 'tf_todo_lists_title';

    /** @var list<string> */
    protected $fillable = ['account_id', 'todo_list_id', 'title'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'todo_list_id' => 'int'];
}
