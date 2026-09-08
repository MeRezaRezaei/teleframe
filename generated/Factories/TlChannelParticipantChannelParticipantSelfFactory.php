<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelParticipantChannelParticipantSelf (channelParticipantSelf). */
final class TlChannelParticipantChannelParticipantSelfFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantSelf> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantSelf::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'via_request' => true,
            'user_id' => 1003,
            'inviter_id' => 1004,
            'date' => 5,
            'subscription_until_date' => 6,
            'rank' => 'rank-7',
        ];
    }
}
