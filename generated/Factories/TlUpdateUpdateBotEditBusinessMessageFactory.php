<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotEditBusinessMessage (updateBotEditBusinessMessage). */
final class TlUpdateUpdateBotEditBusinessMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotEditBusinessMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotEditBusinessMessage::class;

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
