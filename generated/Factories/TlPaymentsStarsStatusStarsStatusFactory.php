<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsStarsStatusStarsStatus (payments.starsStatus). */
final class TlPaymentsStarsStatusStarsStatusFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsStatusStarsStatus> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsStatusStarsStatus::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'balance' => 1002,
            'subscriptions_next_offset' => 'subscriptions_next_offset-3',
            'subscriptions_missing_balance' => 1004,
            'next_offset' => 'next_offset-5',
        ];
    }
}
