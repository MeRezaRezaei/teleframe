<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChannelParticipant (updateChannelParticipant). */
final class TlUpdateUpdateChannelParticipantFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelParticipant> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelParticipant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'via_chatlist' => true,
            'channel_id' => 1003,
            'date' => 4,
            'actor_id' => 1005,
            'user_id' => 1006,
            'prev_participant' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_participant' => (string) new \Symfony\Component\Uid\UuidV7(),
            'invite' => (string) new \Symfony\Component\Uid\UuidV7(),
            'qts' => 10,
        ];
    }
}
