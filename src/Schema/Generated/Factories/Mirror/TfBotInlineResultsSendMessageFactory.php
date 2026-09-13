<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsSendMessage;

class TfBotInlineResultsSendMessageFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsSendMessage::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'invert_media' => fake()->boolean(),
            'message' => fake()->word(),
            'no_webpage' => fake()->boolean(),
            'heading' => fake()->numberBetween(0, 2147483647),
            'period' => fake()->numberBetween(0, 2147483647),
            'proximity_notification_radius' => fake()->numberBetween(0, 2147483647),
            'title' => fake()->word(),
            'address' => fake()->word(),
            'provider' => fake()->word(),
            'venue_id' => fake()->word(),
            'venue_type' => fake()->word(),
            'phone_number' => fake()->word(),
            'first_name' => fake()->word(),
            'last_name' => fake()->word(),
            'vcard' => fake()->word(),
            'shipping_address_requested' => fake()->boolean(),
            'test' => fake()->boolean(),
            'description' => fake()->word(),
            'currency' => fake()->word(),
            'total_amount' => fake()->unique()->randomNumber(8),
            'force_large_media' => fake()->boolean(),
            'force_small_media' => fake()->boolean(),
            'manual' => fake()->boolean(),
            'safe' => fake()->boolean(),
            'url' => fake()->word(),
        ];
    }
}
