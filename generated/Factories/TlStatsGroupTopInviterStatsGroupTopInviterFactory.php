<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsGroupTopInviterStatsGroupTopInviter (statsGroupTopInviter). */
final class TlStatsGroupTopInviterStatsGroupTopInviterFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGroupTopInviterStatsGroupTopInviter> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGroupTopInviterStatsGroupTopInviter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'invitations' => 2,
        ];
    }
}
