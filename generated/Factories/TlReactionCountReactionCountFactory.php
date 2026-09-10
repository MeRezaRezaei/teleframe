<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlReactionCountReactionCount (reactionCount). */
final class TlReactionCountReactionCountFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionCountReactionCount> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionCountReactionCount::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'chosen_order' => 2,
            'reaction' => 1003,
            'count' => 4,
        ];
    }
}
