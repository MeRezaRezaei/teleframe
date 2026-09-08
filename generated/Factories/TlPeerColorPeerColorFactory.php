<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPeerColorPeerColor (peerColor). */
final class TlPeerColorPeerColorFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColorPeerColor> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColorPeerColor::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'color' => 2,
            'background_emoji_id' => 1003,
        ];
    }
}
