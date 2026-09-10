<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelParticipantChannelParticipantBanned (channelParticipantBanned). */
final class TlChannelParticipantChannelParticipantBannedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantBanned> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantBanned::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'left' => true,
            'peer' => 1003,
            'kicked_by' => 1004,
            'date' => 5,
            'banned_rights' => 1006,
            'rank' => 'rank-7',
        ];
    }
}
