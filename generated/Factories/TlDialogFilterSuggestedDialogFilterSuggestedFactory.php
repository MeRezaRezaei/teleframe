<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDialogFilterSuggestedDialogFilterSuggested (dialogFilterSuggested). */
final class TlDialogFilterSuggestedDialogFilterSuggestedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterSuggestedDialogFilterSuggested> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterSuggestedDialogFilterSuggested::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'filter' => (string) new \Symfony\Component\Uid\UuidV7(),
            'description' => 'description-2',
        ];
    }
}
