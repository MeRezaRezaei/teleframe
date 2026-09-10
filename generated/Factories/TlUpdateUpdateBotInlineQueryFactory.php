<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotInlineQuery (updateBotInlineQuery). */
final class TlUpdateUpdateBotInlineQueryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotInlineQuery> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotInlineQuery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'query_id' => 1002,
            'user_id' => 1003,
            'query' => 'query-4',
            'geo' => 1005,
            'peer_type' => 1006,
            'tl_offset' => 'offset-7',
        ];
    }
}
