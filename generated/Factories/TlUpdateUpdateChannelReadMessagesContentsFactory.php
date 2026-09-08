<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChannelReadMessagesContents (updateChannelReadMessagesContents). */
final class TlUpdateUpdateChannelReadMessagesContentsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelReadMessagesContents> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelReadMessagesContents::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'channel_id' => 1002,
            'top_msg_id' => 3,
            'saved_peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
