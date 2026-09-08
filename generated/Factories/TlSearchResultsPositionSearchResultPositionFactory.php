<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSearchResultsPositionSearchResultPosition (searchResultPosition). */
final class TlSearchResultsPositionSearchResultPositionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSearchResultsPositionSearchResultPosition> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSearchResultsPositionSearchResultPosition::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'msg_id' => 1,
            'date' => 2,
            'tl_offset' => 3,
        ];
    }
}
