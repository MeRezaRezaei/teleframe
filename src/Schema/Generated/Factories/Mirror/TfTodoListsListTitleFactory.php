<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfTodoListsListTitle;

class TfTodoListsListTitleFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfTodoListsListTitle::class;

    public function definition(): array
    {
        return [
            'todo_list_id' => fake()->unique()->randomNumber(8),
            'text' => fake()->word(),
        ];
    }
}
