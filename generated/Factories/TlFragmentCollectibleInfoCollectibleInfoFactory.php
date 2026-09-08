<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlFragmentCollectibleInfoCollectibleInfo (fragment.collectibleInfo). */
final class TlFragmentCollectibleInfoCollectibleInfoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFragmentCollectibleInfoCollectibleInfo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFragmentCollectibleInfoCollectibleInfo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'purchase_date' => 1,
            'currency' => 'currency-2',
            'amount' => 1003,
            'crypto_currency' => 'crypto_currency-4',
            'crypto_amount' => 1005,
            'url' => 'url-6',
        ];
    }
}
