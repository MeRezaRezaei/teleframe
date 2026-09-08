<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftCollectionStarGiftCollection (starGiftCollection). */
final class TlStarGiftCollectionStarGiftCollectionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftCollectionStarGiftCollection> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftCollectionStarGiftCollection::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'collection_id' => 2,
            'title' => 'title-3',
            'icon' => (string) new \Symfony\Component\Uid\UuidV7(),
            'gifts_count' => 5,
            'hash' => 1006,
        ];
    }
}
