<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAiComposeToneAiComposeTone (aiComposeTone). */
final class TlAiComposeToneAiComposeToneFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAiComposeToneAiComposeTone> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAiComposeToneAiComposeTone::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'creator' => true,
            'tl_id' => 1003,
            'access_hash' => 1004,
            'slug' => 'slug-5',
            'title' => 'title-6',
            'emoji_id' => 1007,
            'prompt' => 'prompt-8',
            'installs_count' => 9,
            'author_id' => 1010,
            'example_english' => 1011,
        ];
    }
}
