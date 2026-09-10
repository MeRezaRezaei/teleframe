<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlKeyboardButtonKeyboardButtonRequestPeer (keyboardButtonRequestPeer). */
final class TlKeyboardButtonKeyboardButtonRequestPeerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestPeer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestPeer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'style' => 1002,
            'text' => 'text-3',
            'button_id' => 4,
            'peer_type' => 1005,
            'max_quantity' => 6,
        ];
    }
}
