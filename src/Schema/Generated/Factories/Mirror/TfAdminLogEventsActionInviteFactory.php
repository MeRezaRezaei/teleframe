<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEventsActionInvite;

class TfAdminLogEventsActionInviteFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEventsActionInvite::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'revoked' => fake()->boolean(),
            'permanent' => fake()->boolean(),
            'request_needed' => fake()->boolean(),
            'link' => fake()->word(),
            'admin_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
            'start_date' => fake()->numberBetween(0, 2147483647),
            'expire_date' => fake()->numberBetween(0, 2147483647),
            'usage_limit' => fake()->numberBetween(0, 2147483647),
            'usage' => fake()->numberBetween(0, 2147483647),
            'requested' => fake()->numberBetween(0, 2147483647),
            'subscription_expired' => fake()->numberBetween(0, 2147483647),
            'title' => fake()->word(),
        ];
    }
}
