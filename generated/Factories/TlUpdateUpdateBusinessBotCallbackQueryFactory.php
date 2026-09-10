<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBusinessBotCallbackQuery (updateBusinessBotCallbackQuery). */
final class TlUpdateUpdateBusinessBotCallbackQueryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBusinessBotCallbackQuery> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBusinessBotCallbackQuery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'query_id' => 1002,
            'user_id' => 1003,
            'connection_id' => 'connection_id-4',
            'message' => 1005,
            'reply_to_message' => 1006,
            'chat_instance' => 1007,
            'data' => 'Ynl0ZXMtOA==',
        ];
    }
}
