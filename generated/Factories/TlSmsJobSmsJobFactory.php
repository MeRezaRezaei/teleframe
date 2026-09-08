<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSmsJobSmsJob (smsJob). */
final class TlSmsJobSmsJobFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSmsJobSmsJob> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSmsJobSmsJob::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'job_id' => 'job_id-1',
            'phone_number' => 'phone_number-2',
            'text' => 'text-3',
        ];
    }
}
