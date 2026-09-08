<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoriesStoryViewsListStoryViewsList (stories.storyViewsList). */
final class TlStoriesStoryViewsListStoryViewsListFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryViewsListStoryViewsList> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryViewsListStoryViewsList::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'count' => 2,
            'views_count' => 3,
            'forwards_count' => 4,
            'reactions_count' => 5,
            'next_offset' => 'next_offset-6',
        ];
    }
}
