<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGroupCallDonorGroupCallDonor (groupCallDonor). */
final class TlGroupCallDonorGroupCallDonorFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallDonorGroupCallDonor> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallDonorGroupCallDonor::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'top' => true,
            'my' => true,
            'peer_id' => 1004,
            'stars' => 1005,
        ];
    }
}
