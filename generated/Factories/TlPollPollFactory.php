<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPollPoll (poll). */
final class TlPollPollFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollPoll> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollPoll::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'flags' => 2,
            'closed' => true,
            'public_voters' => true,
            'multiple_choice' => true,
            'quiz' => true,
            'open_answers' => true,
            'revoting_disabled' => true,
            'shuffle_answers' => true,
            'hide_results_until_close' => true,
            'creator' => true,
            'subscribers_only' => true,
            'question' => 1013,
            'close_period' => 14,
            'close_date' => 15,
            'hash' => 1016,
        ];
    }
}
