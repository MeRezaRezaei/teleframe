<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotChatInviteRequester (updateBotChatInviteRequester). */
final class TlUpdateUpdateBotChatInviteRequesterFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotChatInviteRequester> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotChatInviteRequester::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'peer' => 1002,
            'date' => 3,
            'user_id' => 1004,
            'about' => 'about-5',
            'invite' => 1006,
            'qts' => 7,
            'query_id' => 1008,
        ];
    }
}
