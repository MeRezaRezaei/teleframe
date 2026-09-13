<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsStarrefPeer;

class TfStarsTransactionsStarrefPeerFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsStarrefPeer::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'starref_peer_type' => fake()->numberBetween(1, 3),
            'starref_peer_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
