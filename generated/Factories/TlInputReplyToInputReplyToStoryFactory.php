<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputReplyToInputReplyToStory (inputReplyToStory). */
final class TlInputReplyToInputReplyToStoryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputReplyToInputReplyToStory> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputReplyToInputReplyToStory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'story_id' => 2,
        ];
    }
}
