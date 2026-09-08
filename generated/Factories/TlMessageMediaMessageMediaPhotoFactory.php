<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaPhoto (messageMediaPhoto). */
final class TlMessageMediaMessageMediaPhotoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPhoto> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'spoiler' => true,
            'live_photo' => true,
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'ttl_seconds' => 5,
            'video' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
