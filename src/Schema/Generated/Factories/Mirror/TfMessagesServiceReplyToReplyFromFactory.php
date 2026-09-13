<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesServiceReplyToReplyFrom;

class TfMessagesServiceReplyToReplyFromFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesServiceReplyToReplyFrom::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'imported' => fake()->boolean(),
            'saved_out' => fake()->boolean(),
            'from_id_type' => fake()->numberBetween(1, 3),
            'from_id_id' => fake()->unique()->randomNumber(8),
            'from_name' => fake()->word(),
            'date' => fake()->numberBetween(0, 2147483647),
            'channel_post' => fake()->numberBetween(0, 2147483647),
            'post_author' => fake()->word(),
            'saved_from_peer_type' => fake()->numberBetween(1, 3),
            'saved_from_peer_id' => fake()->unique()->randomNumber(8),
            'saved_from_msg_id' => fake()->numberBetween(0, 2147483647),
            'saved_from_id_type' => fake()->numberBetween(1, 3),
            'saved_from_id_id' => fake()->unique()->randomNumber(8),
            'saved_from_name' => fake()->word(),
            'saved_date' => fake()->numberBetween(0, 2147483647),
            'psa_type' => fake()->word(),
        ];
    }
}
