<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUserStatusUserStatusRecently (userStatusRecently). */
final class TlUserStatusUserStatusRecentlyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserStatusUserStatusRecently> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserStatusUserStatusRecently::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'by_me' => true,
        ];
    }
}
