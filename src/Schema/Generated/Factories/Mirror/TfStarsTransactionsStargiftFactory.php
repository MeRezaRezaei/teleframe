<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsStargift;

class TfStarsTransactionsStargiftFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsStargift::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'limited' => fake()->boolean(),
            'sold_out' => fake()->boolean(),
            'birthday' => fake()->boolean(),
            'require_premium' => fake()->boolean(),
            'limited_per_user' => fake()->boolean(),
            'peer_color_available' => fake()->boolean(),
            'auction' => fake()->boolean(),
            'stars' => fake()->unique()->randomNumber(8),
            'availability_remains' => fake()->numberBetween(0, 2147483647),
            'availability_total' => fake()->numberBetween(0, 2147483647),
            'availability_resale' => fake()->unique()->randomNumber(8),
            'convert_stars' => fake()->unique()->randomNumber(8),
            'first_sale_date' => fake()->numberBetween(0, 2147483647),
            'last_sale_date' => fake()->numberBetween(0, 2147483647),
            'upgrade_stars' => fake()->unique()->randomNumber(8),
            'resell_min_stars' => fake()->unique()->randomNumber(8),
            'title' => fake()->word(),
            'released_by_type' => fake()->numberBetween(1, 3),
            'released_by_id' => fake()->unique()->randomNumber(8),
            'per_user_total' => fake()->numberBetween(0, 2147483647),
            'per_user_remains' => fake()->numberBetween(0, 2147483647),
            'locked_until_date' => fake()->numberBetween(0, 2147483647),
            'auction_slug' => fake()->word(),
            'gifts_per_round' => fake()->numberBetween(0, 2147483647),
            'auction_start_date' => fake()->numberBetween(0, 2147483647),
            'upgrade_variants' => fake()->numberBetween(0, 2147483647),
            'resale_ton_only' => fake()->boolean(),
            'theme_available' => fake()->boolean(),
            'burned' => fake()->boolean(),
            'crafted' => fake()->boolean(),
            'gift_id' => fake()->unique()->randomNumber(8),
            'slug' => fake()->word(),
            'num' => fake()->numberBetween(0, 2147483647),
            'owner_id_type' => fake()->numberBetween(1, 3),
            'owner_id_id' => fake()->unique()->randomNumber(8),
            'owner_name' => fake()->word(),
            'owner_address' => fake()->word(),
            'availability_issued' => fake()->numberBetween(0, 2147483647),
            'gift_address' => fake()->word(),
            'value_amount' => fake()->unique()->randomNumber(8),
            'value_currency' => fake()->word(),
            'value_usd_amount' => fake()->unique()->randomNumber(8),
            'theme_peer_type' => fake()->numberBetween(1, 3),
            'theme_peer_id' => fake()->unique()->randomNumber(8),
            'host_id_type' => fake()->numberBetween(1, 3),
            'host_id_id' => fake()->unique()->randomNumber(8),
            'offer_min_stars' => fake()->numberBetween(0, 2147483647),
            'craft_chance_permille' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
