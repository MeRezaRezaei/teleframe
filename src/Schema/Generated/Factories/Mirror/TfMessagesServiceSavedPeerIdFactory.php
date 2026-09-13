<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesServiceSavedPeerId;

class TfMessagesServiceSavedPeerIdFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesServiceSavedPeerId::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'saved_peer_id_type' => fake()->numberBetween(1, 3),
            'saved_peer_id_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
