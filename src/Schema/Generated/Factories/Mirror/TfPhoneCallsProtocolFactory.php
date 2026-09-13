<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfPhoneCallsProtocol;

class TfPhoneCallsProtocolFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfPhoneCallsProtocol::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'udp_p2p' => fake()->boolean(),
            'udp_reflector' => fake()->boolean(),
            'min_layer' => fake()->numberBetween(0, 2147483647),
            'max_layer' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
