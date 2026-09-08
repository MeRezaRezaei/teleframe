<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlCdnPublicKeyCdnPublicKey (cdnPublicKey). */
final class TlCdnPublicKeyCdnPublicKeyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlCdnPublicKeyCdnPublicKey> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlCdnPublicKeyCdnPublicKey::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'dc_id' => 1,
            'public_key' => 'public_key-2',
        ];
    }
}
