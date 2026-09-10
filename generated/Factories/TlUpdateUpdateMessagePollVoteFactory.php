<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateMessagePollVote (updateMessagePollVote). */
final class TlUpdateUpdateMessagePollVoteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePollVote> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePollVote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'poll_id' => 1001,
            'peer' => 1002,
            'qts' => 3,
        ];
    }
}
