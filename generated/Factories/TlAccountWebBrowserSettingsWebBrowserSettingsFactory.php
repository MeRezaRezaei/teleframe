<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountWebBrowserSettingsWebBrowserSettings (account.webBrowserSettings). */
final class TlAccountWebBrowserSettingsWebBrowserSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountWebBrowserSettingsWebBrowserSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountWebBrowserSettingsWebBrowserSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'open_external_browser' => true,
            'display_close_button' => true,
            'hash' => 1004,
        ];
    }
}
