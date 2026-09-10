<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryViewStoryViewPublicForward (storyViewPublicForward). */
final class TlStoryViewStoryViewPublicForwardFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewStoryViewPublicForward> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewStoryViewPublicForward::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'blocked' => true,
            'blocked_my_stories_from' => true,
            'message' => 1004,
        ];
    }
}
