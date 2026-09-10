<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlVideoSizeVideoSizeStickerMarkup (videoSizeStickerMarkup). */
final class TlVideoSizeVideoSizeStickerMarkupFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlVideoSizeVideoSizeStickerMarkup> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlVideoSizeVideoSizeStickerMarkup::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'stickerset' => 1001,
            'sticker_id' => 1002,
        ];
    }
}
