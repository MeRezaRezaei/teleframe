<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateNewStickerSet (updateNewStickerSet). */
final class TlUpdateUpdateNewStickerSetFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewStickerSet> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewStickerSet::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'stickerset' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
