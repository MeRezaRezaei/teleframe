<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputFileLocationInputStickerSetThumb (inputStickerSetThumb). */
final class TlInputFileLocationInputStickerSetThumbFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputStickerSetThumb> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileLocationInputStickerSetThumb::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'stickerset' => 1001,
            'thumb_version' => 2,
        ];
    }
}
