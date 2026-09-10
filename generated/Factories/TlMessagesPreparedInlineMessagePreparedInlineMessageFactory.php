<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesPreparedInlineMessagePreparedInlineMessage (messages.preparedInlineMessage). */
final class TlMessagesPreparedInlineMessagePreparedInlineMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPreparedInlineMessagePreparedInlineMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPreparedInlineMessagePreparedInlineMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'query_id' => 1001,
            'result' => 1002,
            'cache_time' => 3,
        ];
    }
}
