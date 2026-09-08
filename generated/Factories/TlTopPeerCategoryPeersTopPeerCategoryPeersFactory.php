<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlTopPeerCategoryPeersTopPeerCategoryPeers (topPeerCategoryPeers). */
final class TlTopPeerCategoryPeersTopPeerCategoryPeersFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTopPeerCategoryPeersTopPeerCategoryPeers> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTopPeerCategoryPeersTopPeerCategoryPeers::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'category' => (string) new \Symfony\Component\Uid\UuidV7(),
            'count' => 2,
        ];
    }
}
