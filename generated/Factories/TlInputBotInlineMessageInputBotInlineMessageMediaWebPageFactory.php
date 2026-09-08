<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotInlineMessageInputBotInlineMessageMediaWebPage (inputBotInlineMessageMediaWebPage). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaWebPageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaWebPage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaWebPage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'invert_media' => true,
            'force_large_media' => true,
            'force_small_media' => true,
            'optional' => true,
            'message' => 'message-6',
            'url' => 'url-7',
            'reply_markup' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
