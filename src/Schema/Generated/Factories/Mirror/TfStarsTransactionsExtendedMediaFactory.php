<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMedia;

class TfStarsTransactionsExtendedMediaFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMedia::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'spoiler' => fake()->boolean(),
            'live_photo' => fake()->boolean(),
            'ttl_seconds' => fake()->numberBetween(0, 2147483647),
            'phone_number' => fake()->word(),
            'first_name' => fake()->word(),
            'last_name' => fake()->word(),
            'vcard' => fake()->word(),
            'user_id' => fake()->unique()->randomNumber(8),
            'nopremium' => fake()->boolean(),
            'video' => fake()->boolean(),
            'round' => fake()->boolean(),
            'voice' => fake()->boolean(),
            'video_timestamp' => fake()->numberBetween(0, 2147483647),
            'force_large_media' => fake()->boolean(),
            'force_small_media' => fake()->boolean(),
            'manual' => fake()->boolean(),
            'safe' => fake()->boolean(),
            'title' => fake()->word(),
            'address' => fake()->word(),
            'provider' => fake()->word(),
            'venue_id' => fake()->word(),
            'venue_type' => fake()->word(),
            'shipping_address_requested' => fake()->boolean(),
            'test' => fake()->boolean(),
            'description' => fake()->word(),
            'receipt_msg_id' => fake()->numberBetween(0, 2147483647),
            'currency' => fake()->word(),
            'total_amount' => fake()->unique()->randomNumber(8),
            'start_param' => fake()->word(),
            'heading' => fake()->numberBetween(0, 2147483647),
            'period' => fake()->numberBetween(0, 2147483647),
            'proximity_notification_radius' => fake()->numberBetween(0, 2147483647),
            'value' => fake()->numberBetween(0, 2147483647),
            'emoticon' => fake()->word(),
            'via_mention' => fake()->boolean(),
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'only_new_subscribers' => fake()->boolean(),
            'winners_are_visible' => fake()->boolean(),
            'prize_description' => fake()->word(),
            'quantity' => fake()->numberBetween(0, 2147483647),
            'months' => fake()->numberBetween(0, 2147483647),
            'stars' => fake()->unique()->randomNumber(8),
            'until_date' => fake()->numberBetween(0, 2147483647),
            'refunded' => fake()->boolean(),
            'channel_id' => fake()->unique()->randomNumber(8),
            'additional_peers_count' => fake()->numberBetween(0, 2147483647),
            'launch_msg_id' => fake()->numberBetween(0, 2147483647),
            'winners_count' => fake()->numberBetween(0, 2147483647),
            'unclaimed_count' => fake()->numberBetween(0, 2147483647),
            'stars_amount' => fake()->unique()->randomNumber(8),
            'rtmp_stream' => fake()->boolean(),
        ];
    }
}
