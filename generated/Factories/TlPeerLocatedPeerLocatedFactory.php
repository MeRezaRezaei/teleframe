<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPeerLocatedPeerLocated (peerLocated). */
final class TlPeerLocatedPeerLocatedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerLocatedPeerLocated> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerLocatedPeerLocated::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => 1001,
            'expires' => 2,
            'distance' => 3,
        ];
    }
}
