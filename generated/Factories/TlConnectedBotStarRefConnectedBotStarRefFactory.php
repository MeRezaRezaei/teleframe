<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlConnectedBotStarRefConnectedBotStarRef (connectedBotStarRef). */
final class TlConnectedBotStarRefConnectedBotStarRefFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConnectedBotStarRefConnectedBotStarRef> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConnectedBotStarRefConnectedBotStarRef::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'revoked' => true,
            'url' => 'url-3',
            'date' => 4,
            'bot_id' => 1005,
            'commission_permille' => 6,
            'duration_months' => 7,
            'participants' => 1008,
            'revenue' => 1009,
        ];
    }
}
