<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaDocument (messageMediaDocument). */
final class TlMessageMediaMessageMediaDocumentFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDocument> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDocument::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'nopremium' => true,
            'spoiler' => true,
            'video' => true,
            'round' => true,
            'voice' => true,
            'document' => 1007,
            'video_cover' => 1008,
            'video_timestamp' => 9,
            'ttl_seconds' => 10,
        ];
    }
}
