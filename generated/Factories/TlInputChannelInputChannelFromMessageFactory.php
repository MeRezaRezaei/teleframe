<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputChannelInputChannelFromMessage (inputChannelFromMessage). */
final class TlInputChannelInputChannelFromMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChannelInputChannelFromMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChannelInputChannelFromMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => 1001,
            'msg_id' => 2,
            'channel_id' => 1003,
        ];
    }
}
