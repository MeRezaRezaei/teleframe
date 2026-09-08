<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSmsjobsEligibilityToJoinEligibleToJoin (smsjobs.eligibleToJoin). */
final class TlSmsjobsEligibilityToJoinEligibleToJoinFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSmsjobsEligibilityToJoinEligibleToJoin> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSmsjobsEligibilityToJoinEligibleToJoin::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'terms_url' => 'terms_url-1',
            'monthly_sent_sms' => 2,
        ];
    }
}
