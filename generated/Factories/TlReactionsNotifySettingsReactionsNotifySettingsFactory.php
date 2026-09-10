<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlReactionsNotifySettingsReactionsNotifySettings (reactionsNotifySettings). */
final class TlReactionsNotifySettingsReactionsNotifySettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionsNotifySettingsReactionsNotifySettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionsNotifySettingsReactionsNotifySettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'messages_notify_from' => 1002,
            'stories_notify_from' => 1003,
            'poll_votes_notify_from' => 1004,
            'sound' => 1005,
            'show_previews' => 1006,
        ];
    }
}
