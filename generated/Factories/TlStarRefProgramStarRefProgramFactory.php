<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarRefProgramStarRefProgram (starRefProgram). */
final class TlStarRefProgramStarRefProgramFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarRefProgramStarRefProgram> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarRefProgramStarRefProgram::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'bot_id' => 1002,
            'commission_permille' => 3,
            'duration_months' => 4,
            'end_date' => 5,
            'daily_revenue_per_user' => 1006,
        ];
    }
}
