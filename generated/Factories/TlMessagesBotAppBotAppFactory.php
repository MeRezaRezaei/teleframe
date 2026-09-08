<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesBotAppBotApp (messages.botApp). */
final class TlMessagesBotAppBotAppFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotAppBotApp> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotAppBotApp::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'inactive' => true,
            'request_write_access' => true,
            'has_settings' => true,
            'app' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
