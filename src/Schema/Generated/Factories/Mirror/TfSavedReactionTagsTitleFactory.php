<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfSavedReactionTagsTitle;

class TfSavedReactionTagsTitleFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfSavedReactionTagsTitle::class;

    public function definition(): array
    {
        return [
            'saved_reaction_tag_id' => fake()->unique()->randomNumber(8),
            'title' => fake()->word(),
        ];
    }
}
