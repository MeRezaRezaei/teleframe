<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDocumentAttributeDocumentAttributeSticker (documentAttributeSticker). */
final class TlDocumentAttributeDocumentAttributeStickerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentAttributeDocumentAttributeSticker> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentAttributeDocumentAttributeSticker::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'mask' => true,
            'alt' => 'alt-3',
            'stickerset' => (string) new \Symfony\Component\Uid\UuidV7(),
            'mask_coords' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
