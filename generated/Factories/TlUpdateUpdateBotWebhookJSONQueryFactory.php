<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotWebhookJSONQuery (updateBotWebhookJSONQuery). */
final class TlUpdateUpdateBotWebhookJSONQueryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotWebhookJSONQuery> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotWebhookJSONQuery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'query_id' => 1001,
            'data' => 1002,
            'timeout' => 3,
        ];
    }
}
