<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessage;

class TfMessageFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessage::class;

    public function definition(): array
    {
        return [
            'id' => fake()->numberBetween(0, 2147483647),
            'constructor' => fake()->word(),
            'peer_id_type' => fake()->numberBetween(1, 3),
            'peer_id_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
            'message' => fake()->word(),
            'out' => fake()->boolean(),
            'mentioned' => fake()->boolean(),
            'media_unread' => fake()->boolean(),
            'silent' => fake()->boolean(),
            'post' => fake()->boolean(),
            'from_scheduled' => fake()->boolean(),
            'legacy' => fake()->boolean(),
            'edit_hide' => fake()->boolean(),
            'pinned' => fake()->boolean(),
            'noforwards' => fake()->boolean(),
            'invert_media' => fake()->boolean(),
            'offline' => fake()->boolean(),
            'video_processing_pending' => fake()->boolean(),
            'paid_suggested_post_stars' => fake()->boolean(),
            'paid_suggested_post_ton' => fake()->boolean(),
        ];
    }
}
