<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotInlineMessageBotInlineMessageMediaGeo (botInlineMessageMediaGeo). */
final class TlBotInlineMessageBotInlineMessageMediaGeoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaGeo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaGeo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'geo' => 1002,
            'heading' => 3,
            'period' => 4,
            'proximity_notification_radius' => 5,
            'reply_markup' => 1006,
        ];
    }
}
