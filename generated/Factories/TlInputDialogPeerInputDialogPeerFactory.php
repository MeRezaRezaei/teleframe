<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputDialogPeerInputDialogPeer (inputDialogPeer). */
final class TlInputDialogPeerInputDialogPeerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputDialogPeerInputDialogPeer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputDialogPeerInputDialogPeer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
