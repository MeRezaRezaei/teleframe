<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotInlineMessageInputBotInlineMessageRichMessage (inputBotInlineMessageRichMessage). */
final class TlInputBotInlineMessageInputBotInlineMessageRichMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageRichMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageRichMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'reply_markup' => 1002,
            'rich_message' => 1003,
        ];
    }
}
