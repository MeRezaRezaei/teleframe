<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputSecureValueInputSecureValue (inputSecureValue). */
final class TlInputSecureValueInputSecureValueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValue> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_type' => (string) new \Symfony\Component\Uid\UuidV7(),
            'data' => (string) new \Symfony\Component\Uid\UuidV7(),
            'front_side' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reverse_side' => (string) new \Symfony\Component\Uid\UuidV7(),
            'selfie' => (string) new \Symfony\Component\Uid\UuidV7(),
            'plain_data' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
