<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStickerSetCoveredStickerSetCovered (stickerSetCovered). */
final class TlStickerSetCoveredStickerSetCoveredFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetCovered> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetCovered::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'set' => (string) new \Symfony\Component\Uid\UuidV7(),
            'cover' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
