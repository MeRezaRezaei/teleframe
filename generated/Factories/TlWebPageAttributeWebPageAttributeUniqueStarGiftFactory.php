<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWebPageAttributeWebPageAttributeUniqueStarGift (webPageAttributeUniqueStarGift). */
final class TlWebPageAttributeWebPageAttributeUniqueStarGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeUniqueStarGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeUniqueStarGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'gift' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
