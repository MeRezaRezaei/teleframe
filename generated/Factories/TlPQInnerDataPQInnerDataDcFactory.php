<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPQInnerDataPQInnerDataDc (p_q_inner_data_dc). */
final class TlPQInnerDataPQInnerDataDcFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPQInnerDataPQInnerDataDc> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPQInnerDataPQInnerDataDc::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'pq' => 'pq-1',
            'p' => 'p-2',
            'q' => 'q-3',
            'nonce' => '99999999999999999999999999999999999999',
            'server_nonce' => '99999999999999999999999999999999999999',
            'new_nonce' => '99999999999999999999999999999999999999999999999999999999999999999999999999999',
            'dc' => 7,
        ];
    }
}
