<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the TodoItem union (table tf_todo_items).
 * Single ctor (todoItem); title nests under tf_todo_items_title.
 *
 * @property int $account_id
 * @property int $id
 */
final class TfTodoItem extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_todo_items';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
    ];

    public function title(): HasOne
    {
        return $this->hasOne(TfTodoItemTitle::class, 'id', 'id');
    }
}
