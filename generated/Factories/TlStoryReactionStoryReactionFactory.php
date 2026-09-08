<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryReactionStoryReaction (storyReaction). */
final class TlStoryReactionStoryReactionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryReactionStoryReaction> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryReactionStoryReaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 2,
            'reaction' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
