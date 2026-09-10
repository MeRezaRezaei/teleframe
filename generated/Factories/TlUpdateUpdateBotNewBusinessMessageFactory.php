<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotNewBusinessMessage (updateBotNewBusinessMessage). */
final class TlUpdateUpdateBotNewBusinessMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotNewBusinessMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotNewBusinessMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'connection_id' => 'connection_id-2',
            'message' => 1003,
            'reply_to_message' => 1004,
            'qts' => 5,
        ];
    }
}
