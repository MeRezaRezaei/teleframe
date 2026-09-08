<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryViewsStoryViews (storyViews). */
final class TlStoryViewsStoryViewsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewsStoryViews> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewsStoryViews::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'has_viewers' => true,
            'views_count' => 3,
            'forwards_count' => 4,
            'reactions_count' => 5,
        ];
    }
}
