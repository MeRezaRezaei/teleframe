<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesSummaryFromLanguage;

class TfMessagesSummaryFromLanguageFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesSummaryFromLanguage::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'summary_from_language' => fake()->word(),
        ];
    }
}
