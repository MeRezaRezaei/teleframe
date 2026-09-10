<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBusinessAwayMessageBusinessAwayMessage (businessAwayMessage). */
final class TlBusinessAwayMessageBusinessAwayMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessAwayMessageBusinessAwayMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessAwayMessageBusinessAwayMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'offline_only' => true,
            'shortcut_id' => 3,
            'schedule' => 1004,
            'recipients' => 1005,
        ];
    }
}
