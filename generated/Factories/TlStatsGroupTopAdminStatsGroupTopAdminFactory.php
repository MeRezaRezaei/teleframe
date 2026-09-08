<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsGroupTopAdminStatsGroupTopAdmin (statsGroupTopAdmin). */
final class TlStatsGroupTopAdminStatsGroupTopAdminFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGroupTopAdminStatsGroupTopAdmin> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGroupTopAdminStatsGroupTopAdmin::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'deleted' => 2,
            'kicked' => 3,
            'banned' => 4,
        ];
    }
}
