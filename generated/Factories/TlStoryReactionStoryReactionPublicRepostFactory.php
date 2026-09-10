<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryReactionStoryReactionPublicRepost (storyReactionPublicRepost). */
final class TlStoryReactionStoryReactionPublicRepostFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryReactionStoryReactionPublicRepost> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryReactionStoryReactionPublicRepost::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer_id' => 1001,
            'story' => 1002,
        ];
    }
}
