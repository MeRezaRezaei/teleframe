<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotInlineMessageInputBotInlineMessageText (inputBotInlineMessageText). */
final class TlInputBotInlineMessageInputBotInlineMessageTextFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageText> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageText::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'no_webpage' => true,
            'invert_media' => true,
            'message' => 'message-4',
            'reply_markup' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
