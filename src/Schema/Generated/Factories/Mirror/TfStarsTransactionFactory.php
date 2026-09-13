<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransaction;

class TfStarsTransactionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransaction::class;

    public function definition(): array
    {
        return [
            'id' => fake()->word(),
            'date' => fake()->numberBetween(0, 2147483647),
            'refund' => fake()->boolean(),
            'pending' => fake()->boolean(),
            'failed' => fake()->boolean(),
            'gift' => fake()->boolean(),
            'reaction' => fake()->boolean(),
            'stargift_upgrade' => fake()->boolean(),
            'business_transfer' => fake()->boolean(),
            'stargift_resale' => fake()->boolean(),
            'posts_search' => fake()->boolean(),
            'stargift_prepaid_upgrade' => fake()->boolean(),
            'stargift_drop_original_details' => fake()->boolean(),
            'phonegroup_message' => fake()->boolean(),
            'stargift_auction_bid' => fake()->boolean(),
            'offer' => fake()->boolean(),
        ];
    }
}
