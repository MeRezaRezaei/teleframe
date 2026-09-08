<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChatParticipantRank (updateChatParticipantRank). */
final class TlUpdateUpdateChatParticipantRankFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipantRank> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipantRank::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chat_id' => 1001,
            'user_id' => 1002,
            'rank' => 'rank-3',
            'version' => 4,
        ];
    }
}
