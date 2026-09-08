<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAttributeStarGiftAttributePattern (starGiftAttributePattern). */
final class TlStarGiftAttributeStarGiftAttributePatternFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributePattern> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributePattern::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => 'name-1',
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'rarity' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
