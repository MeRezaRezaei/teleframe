<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSecureValueErrorSecureValueError (secureValueError). */
final class TlSecureValueErrorSecureValueErrorFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueError> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueError::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_type' => (string) new \Symfony\Component\Uid\UuidV7(),
            'hash' => 'Ynl0ZXMtMg==',
            'text' => 'text-3',
        ];
    }
}
