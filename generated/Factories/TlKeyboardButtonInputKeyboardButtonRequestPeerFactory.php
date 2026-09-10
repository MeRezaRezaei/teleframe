<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlKeyboardButtonInputKeyboardButtonRequestPeer (inputKeyboardButtonRequestPeer). */
final class TlKeyboardButtonInputKeyboardButtonRequestPeerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonRequestPeer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonRequestPeer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'name_requested' => true,
            'username_requested' => true,
            'photo_requested' => true,
            'style' => 1005,
            'text' => 'text-6',
            'button_id' => 7,
            'peer_type' => 1008,
            'max_quantity' => 9,
        ];
    }
}
