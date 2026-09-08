<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelParticipantsFilterChannelParticipantsMentions (channelParticipantsMentions). */
final class TlChannelParticipantsFilterChannelParticipantsMentionsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantsFilterChannelParticipantsMentions> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantsFilterChannelParticipantsMentions::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'q' => 'q-2',
            'top_msg_id' => 3,
        ];
    }
}
