<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsUniqueStarGiftValueInfoUniqueStarGiftValueInfo (payments.uniqueStarGiftValueInfo). */
final class TlPaymentsUniqueStarGiftValueInfoUniqueStarGiftValueInfoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsUniqueStarGiftValueInfoUniqueStarGiftValueInfo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsUniqueStarGiftValueInfoUniqueStarGiftValueInfo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'last_sale_on_fragment' => true,
            'value_is_average' => true,
            'currency' => 'currency-4',
            'tl_value' => 1005,
            'initial_sale_date' => 6,
            'initial_sale_stars' => 1007,
            'initial_sale_price' => 1008,
            'last_sale_date' => 9,
            'last_sale_price' => 1010,
            'floor_price' => 1011,
            'average_price' => 1012,
            'listed_count' => 13,
            'fragment_listed_count' => 14,
            'fragment_listed_url' => 'fragment_listed_url-15',
        ];
    }
}
