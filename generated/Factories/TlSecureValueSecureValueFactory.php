<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSecureValueSecureValue (secureValue). */
final class TlSecureValueSecureValueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValue> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_type' => 1002,
            'data' => 1003,
            'front_side' => 1004,
            'reverse_side' => 1005,
            'selfie' => 1006,
            'plain_data' => 1007,
            'hash' => 'Ynl0ZXMtOA==',
        ];
    }
}
