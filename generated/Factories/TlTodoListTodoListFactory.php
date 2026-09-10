<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlTodoListTodoList (todoList). */
final class TlTodoListTodoListFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoListTodoList> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoListTodoList::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'others_can_append' => true,
            'others_can_complete' => true,
            'title' => 1004,
        ];
    }
}
