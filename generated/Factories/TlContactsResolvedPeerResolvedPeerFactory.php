<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlContactsResolvedPeerResolvedPeer (contacts.resolvedPeer). */
final class TlContactsResolvedPeerResolvedPeerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsResolvedPeerResolvedPeer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactsResolvedPeerResolvedPeer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
