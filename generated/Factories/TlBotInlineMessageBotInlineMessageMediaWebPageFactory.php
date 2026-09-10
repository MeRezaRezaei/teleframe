<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotInlineMessageBotInlineMessageMediaWebPage (botInlineMessageMediaWebPage). */
final class TlBotInlineMessageBotInlineMessageMediaWebPageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaWebPage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaWebPage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'invert_media' => true,
            'force_large_media' => true,
            'force_small_media' => true,
            'manual' => true,
            'safe' => true,
            'message' => 'message-7',
            'url' => 'url-8',
            'reply_markup' => 1009,
        ];
    }
}
