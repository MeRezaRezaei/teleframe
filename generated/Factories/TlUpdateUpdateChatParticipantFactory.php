<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChatParticipant (updateChatParticipant). */
final class TlUpdateUpdateChatParticipantFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipant> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'chat_id' => 1002,
            'date' => 3,
            'actor_id' => 1004,
            'user_id' => 1005,
            'prev_participant' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_participant' => (string) new \Symfony\Component\Uid\UuidV7(),
            'invite' => (string) new \Symfony\Component\Uid\UuidV7(),
            'qts' => 9,
        ];
    }
}
