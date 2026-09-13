<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelParticipant (domain: channel_participants). */
final class TlChannelParticipantFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipant> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'user_id' => 1002,
            'date' => 3,
            'subscription_until_date' => 4,
            'rank' => 'rank-5',
        ];
    }
}
