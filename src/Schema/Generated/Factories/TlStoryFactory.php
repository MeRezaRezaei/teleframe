<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStory (domain: stories). */
final class TlStoryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStory> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1,
            'flags' => 2,
            'state' => 'state-3',
            'stealth_mode' => 1004,
            'count_remains' => 5,
            'count' => 7,
            'next_offset' => 'next_offset-8',
            'stories' => 1009,
            'views_count' => 17,
            'forwards_count' => 18,
            'reactions_count' => 19,
        ];
    }
}
