<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionBotAllowed (messageActionBotAllowed). */
final class TlMessageActionMessageActionBotAllowedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionBotAllowed> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionBotAllowed::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'attach_menu' => true,
            'from_request' => true,
            'domain' => 'domain-4',
            'app' => 1005,
        ];
    }
}
