<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionGroupCallScheduled (messageActionGroupCallScheduled). */
final class TlMessageActionMessageActionGroupCallScheduledFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGroupCallScheduled> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGroupCallScheduled::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'call' => 1001,
            'schedule_date' => 2,
        ];
    }
}
