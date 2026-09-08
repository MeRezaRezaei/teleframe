<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAttributeStarGiftAttributeModel (starGiftAttributeModel). */
final class TlStarGiftAttributeStarGiftAttributeModelFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeModel> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'crafted' => true,
            'name' => 'name-3',
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'rarity' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
