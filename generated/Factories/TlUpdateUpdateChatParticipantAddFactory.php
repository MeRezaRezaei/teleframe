<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChatParticipantAdd (updateChatParticipantAdd). */
final class TlUpdateUpdateChatParticipantAddFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipantAdd> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipantAdd::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chat_id' => 1001,
            'user_id' => 1002,
            'inviter_id' => 1003,
            'date' => 4,
            'version' => 5,
        ];
    }
}
