<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPostInteractionCountersPostInteractionCountersStory (postInteractionCountersStory). */
final class TlPostInteractionCountersPostInteractionCountersStoryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPostInteractionCountersPostInteractionCountersStory> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPostInteractionCountersPostInteractionCountersStory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'story_id' => 1,
            'views' => 2,
            'forwards' => 3,
            'reactions' => 4,
        ];
    }
}
