<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateMessagePoll (updateMessagePoll). */
final class TlUpdateUpdateMessagePollFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePoll> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePoll::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'peer' => 1002,
            'msg_id' => 3,
            'top_msg_id' => 4,
            'poll_id' => 1005,
            'poll' => 1006,
            'results' => 1007,
        ];
    }
}
