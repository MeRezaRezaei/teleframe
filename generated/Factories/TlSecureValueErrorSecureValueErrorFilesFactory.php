<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSecureValueErrorSecureValueErrorFiles (secureValueErrorFiles). */
final class TlSecureValueErrorSecureValueErrorFilesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFiles> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFiles::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_type' => (string) new \Symfony\Component\Uid\UuidV7(),
            'text' => 'text-2',
        ];
    }
}
