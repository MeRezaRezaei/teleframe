<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_todo_items — the FK→TextWithEntities title
 * fact. Flattened to the scalar text (entities are deferred).
 *
 * @property int $account_id
 * @property int $id
 * @property string $title
 */
final class TfTodoItemTitle extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_todo_items_title';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
    ];

    public function todoItem(): BelongsTo
    {
        return $this->belongsTo(TfTodoItem::class, 'id', 'id');
    }
}
