<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHttpWaitHttpWait (http_wait). */
final class TlHttpWaitHttpWaitFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHttpWaitHttpWait> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHttpWaitHttpWait::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'max_delay' => 1,
            'wait_after' => 2,
            'max_wait' => 3,
        ];
    }
}
