<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesBotCallbackAnswerBotCallbackAnswer (messages.botCallbackAnswer). */
final class TlMessagesBotCallbackAnswerBotCallbackAnswerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotCallbackAnswerBotCallbackAnswer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotCallbackAnswerBotCallbackAnswer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'alert' => true,
            'has_url' => true,
            'native_ui' => true,
            'message' => 'message-5',
            'url' => 'url-6',
            'cache_time' => 7,
        ];
    }
}
