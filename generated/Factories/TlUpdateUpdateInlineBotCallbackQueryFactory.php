<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateInlineBotCallbackQuery (updateInlineBotCallbackQuery). */
final class TlUpdateUpdateInlineBotCallbackQueryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateInlineBotCallbackQuery> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateInlineBotCallbackQuery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'query_id' => 1002,
            'user_id' => 1003,
            'msg_id' => 1004,
            'chat_instance' => 1005,
            'data' => 'Ynl0ZXMtNg==',
            'game_short_name' => 'game_short_name-7',
        ];
    }
}
