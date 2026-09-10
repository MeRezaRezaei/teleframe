<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSavedReactionTagSavedReactionTag (savedReactionTag). */
final class TlSavedReactionTagSavedReactionTagFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedReactionTagSavedReactionTag> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedReactionTagSavedReactionTag::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'reaction' => 1002,
            'title' => 'title-3',
            'count' => 4,
        ];
    }
}
