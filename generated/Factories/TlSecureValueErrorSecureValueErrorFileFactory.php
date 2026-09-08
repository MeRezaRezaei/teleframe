<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSecureValueErrorSecureValueErrorFile (secureValueErrorFile). */
final class TlSecureValueErrorSecureValueErrorFileFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFile> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_type' => (string) new \Symfony\Component\Uid\UuidV7(),
            'file_hash' => 'Ynl0ZXMtMg==',
            'text' => 'text-3',
        ];
    }
}
