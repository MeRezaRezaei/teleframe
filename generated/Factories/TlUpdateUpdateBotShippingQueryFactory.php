<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotShippingQuery (updateBotShippingQuery). */
final class TlUpdateUpdateBotShippingQueryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotShippingQuery> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotShippingQuery::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'query_id' => 1001,
            'user_id' => 1002,
            'payload' => 'Ynl0ZXMtMw==',
            'shipping_address' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
