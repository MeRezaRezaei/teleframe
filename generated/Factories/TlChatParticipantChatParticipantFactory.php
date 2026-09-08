<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatParticipantChatParticipant (chatParticipant). */
final class TlChatParticipantChatParticipantFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipantChatParticipant> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipantChatParticipant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'user_id' => 1002,
            'inviter_id' => 1003,
            'date' => 4,
            'rank' => 'rank-5',
        ];
    }
}
