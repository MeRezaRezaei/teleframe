<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessageMediasTtlSecond;

class TfMessageMediasTtlSecondFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessageMediasTtlSecond::class;

    public function definition(): array
    {
        return [
            'message_media_id' => fake()->unique()->randomNumber(8),
            'ttl_seconds' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
