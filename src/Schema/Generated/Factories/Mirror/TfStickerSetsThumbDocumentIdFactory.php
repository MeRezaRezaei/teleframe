<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStickerSetsThumbDocumentId;

class TfStickerSetsThumbDocumentIdFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStickerSetsThumbDocumentId::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'thumb_document_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
