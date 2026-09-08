<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlServerDHInnerDataServerDHInnerData (server_DH_inner_data). */
final class TlServerDHInnerDataServerDHInnerDataFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlServerDHInnerDataServerDHInnerData> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlServerDHInnerDataServerDHInnerData::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nonce' => '99999999999999999999999999999999999999',
            'server_nonce' => '99999999999999999999999999999999999999',
            'g' => 3,
            'dh_prime' => 'dh_prime-4',
            'g_a' => 'g_a-5',
            'server_time' => 6,
        ];
    }
}
