<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryItemStoryItemSkipped (storyItemSkipped). */
final class TlStoryItemStoryItemSkippedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItemSkipped> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItemSkipped::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'close_friends' => true,
            'live' => true,
            'tl_id' => 4,
            'date' => 5,
            'expire_date' => 6,
        ];
    }
}
