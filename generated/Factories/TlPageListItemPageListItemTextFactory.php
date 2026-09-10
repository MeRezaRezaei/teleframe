<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageListItemPageListItemText (pageListItemText). */
final class TlPageListItemPageListItemTextFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageListItemPageListItemText> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageListItemPageListItemText::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'checkbox' => true,
            'checked' => true,
            'text' => 1004,
        ];
    }
}
