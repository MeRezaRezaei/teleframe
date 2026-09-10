<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputStickerSetItemInputStickerSetItem (inputStickerSetItem). */
final class TlInputStickerSetItemInputStickerSetItemFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickerSetItemInputStickerSetItem> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickerSetItemInputStickerSetItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'document' => 1002,
            'emoji' => 'emoji-3',
            'mask_coords' => 1004,
            'keywords' => 'keywords-5',
        ];
    }
}
