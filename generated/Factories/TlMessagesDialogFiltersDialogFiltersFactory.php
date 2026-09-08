<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesDialogFiltersDialogFilters (messages.dialogFilters). */
final class TlMessagesDialogFiltersDialogFiltersFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogFiltersDialogFilters> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogFiltersDialogFilters::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tags_enabled' => true,
        ];
    }
}
