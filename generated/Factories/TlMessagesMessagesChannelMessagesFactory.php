<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesMessagesChannelMessages (messages.channelMessages). */
final class TlMessagesMessagesChannelMessagesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesChannelMessages> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesChannelMessages::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'inexact' => true,
            'pts' => 3,
            'count' => 4,
            'offset_id_offset' => 5,
        ];
    }
}
