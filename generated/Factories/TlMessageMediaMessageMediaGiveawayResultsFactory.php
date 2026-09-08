<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaGiveawayResults (messageMediaGiveawayResults). */
final class TlMessageMediaMessageMediaGiveawayResultsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGiveawayResults> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGiveawayResults::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'only_new_subscribers' => true,
            'refunded' => true,
            'channel_id' => 1004,
            'additional_peers_count' => 5,
            'launch_msg_id' => 6,
            'winners_count' => 7,
            'unclaimed_count' => 8,
            'months' => 9,
            'stars' => 1010,
            'prize_description' => 'prize_description-11',
            'until_date' => 12,
        ];
    }
}
