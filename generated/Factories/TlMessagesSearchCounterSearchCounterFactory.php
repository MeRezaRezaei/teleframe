<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesSearchCounterSearchCounter (messages.searchCounter). */
final class TlMessagesSearchCounterSearchCounterFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchCounterSearchCounter> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchCounterSearchCounter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'inexact' => true,
            'filter' => 1003,
            'count' => 4,
        ];
    }
}
