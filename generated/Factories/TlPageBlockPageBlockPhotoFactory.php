<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockPhoto (pageBlockPhoto). */
final class TlPageBlockPageBlockPhotoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockPhoto> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockPhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'spoiler' => true,
            'photo_id' => 1003,
            'caption' => (string) new \Symfony\Component\Uid\UuidV7(),
            'url' => 'url-5',
            'webpage_id' => 1006,
        ];
    }
}
