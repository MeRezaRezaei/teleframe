<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlTodoCompletionTodoCompletion (todoCompletion). */
final class TlTodoCompletionTodoCompletionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoCompletionTodoCompletion> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoCompletionTodoCompletion::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1,
            'completed_by' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 3,
        ];
    }
}
