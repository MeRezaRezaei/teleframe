<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSecureRequiredTypeSecureRequiredType (secureRequiredType). */
final class TlSecureRequiredTypeSecureRequiredTypeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureRequiredTypeSecureRequiredType> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureRequiredTypeSecureRequiredType::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'native_names' => true,
            'selfie_required' => true,
            'translation_required' => true,
            'tl_type' => 1005,
        ];
    }
}
