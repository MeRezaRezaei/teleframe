<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfTodoListsList;

class TfTodoListsListFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfTodoListsList::class;

    public function definition(): array
    {
        return [
            'todo_list_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
