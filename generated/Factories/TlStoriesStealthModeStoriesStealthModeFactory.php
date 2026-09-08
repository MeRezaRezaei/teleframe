<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoriesStealthModeStoriesStealthMode (storiesStealthMode). */
final class TlStoriesStealthModeStoriesStealthModeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStealthModeStoriesStealthMode> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStealthModeStoriesStealthMode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'active_until_date' => 2,
            'cooldown_until_date' => 3,
        ];
    }
}
