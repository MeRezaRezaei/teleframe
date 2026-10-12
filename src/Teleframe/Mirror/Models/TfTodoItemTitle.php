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
 * The column is `text`, NOT `title`, and the reason is the decomposition rule:
 * a `FK→TextWithEntities` child resolves to the UNION'S OWN single scalar field
 * — `textWithEntities#751f3146 text:string entities:Vector<MessageEntity>`
 * (TL:1668) — so `todoItem#cba9a52f title:TextWithEntities` (TL:2065) lands in
 * a column called `text`. `@property $title` was here until 2026-10-02 and
 * named a column the table does not have.
 *
 * @property int $account_id
 * @property int $id
 * @property string $text
 */
final class TfTodoItemTitle extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

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
