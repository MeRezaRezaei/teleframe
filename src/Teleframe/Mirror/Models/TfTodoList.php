<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the TodoList union (table tf_todo_lists).
 * Real key is composite (account_id, todo_list_id); the synthetic
 * todo_list_id carries the mirror content-key (the TL todoList has no
 * natural id — title and the TodoItem vector are children). Single ctor
 * (todoList) — no constructor discriminator column.
 *
 * @property int $account_id
 * @property int $todo_list_id
 * @property bool $others_can_append
 * @property bool $others_can_complete
 */
final class TfTodoList extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_todo_lists';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'todo_list_id' => 'int',
        'others_can_append' => 'bool',
        'others_can_complete' => 'bool',
    ];

    /** @return array<string, int> */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'todo_list_id' => (int) $this->todo_list_id,
        ];
    }

    /** Query for the 1:1 title child of this list. */
    public function title(): Builder
    {
        return $this->child(TfTodoListTitle::class);
    }

    /** 1:N—the Vector<TodoItem>; caller orders by position. */
    public function list(): Builder
    {
        return $this->child(TfTodoListList::class);
    }

    /** @param  class-string<MirrorChildModel>  $child */
    private function child(string $child): Builder
    {
        return $child::query()
            ->where('account_id', (int) $this->account_id)
            ->where('todo_list_id', (int) $this->todo_list_id);
    }
}
