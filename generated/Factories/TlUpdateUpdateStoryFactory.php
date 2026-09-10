<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateStory (updateStory). */
final class TlUpdateUpdateStoryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStory> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => 1001,
            'story' => 1002,
        ];
    }
}
