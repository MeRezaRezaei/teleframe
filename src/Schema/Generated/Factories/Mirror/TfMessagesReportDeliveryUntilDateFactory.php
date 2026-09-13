<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesReportDeliveryUntilDate;

class TfMessagesReportDeliveryUntilDateFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesReportDeliveryUntilDate::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'report_delivery_until_date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
