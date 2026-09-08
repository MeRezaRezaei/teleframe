<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPeerColorPeerColorCollectible (peerColorCollectible). */
final class TlPeerColorPeerColorCollectibleFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColorPeerColorCollectible> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColorPeerColorCollectible::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'collectible_id' => 1002,
            'gift_emoji_id' => 1003,
            'background_emoji_id' => 1004,
            'accent_color' => 5,
            'dark_accent_color' => 6,
        ];
    }
}
