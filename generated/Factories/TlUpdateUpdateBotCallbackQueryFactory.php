<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotCallbackQuery (updateBotCallbackQuery). */
final class TlUpdateUpdateBotCallbackQueryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotCallbackQuery> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotCallbackQuery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'query_id' => 1002,
            'user_id' => 1003,
            'peer' => 1004,
            'msg_id' => 5,
            'chat_instance' => 1006,
            'data' => 'Ynl0ZXMtNw==',
            'game_short_name' => 'game_short_name-8',
        ];
    }
}
