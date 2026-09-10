<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotBusinessConnectionBotBusinessConnection (botBusinessConnection). */
final class TlBotBusinessConnectionBotBusinessConnectionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotBusinessConnectionBotBusinessConnection> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotBusinessConnectionBotBusinessConnection::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'disabled' => true,
            'connection_id' => 'connection_id-3',
            'user_id' => 1004,
            'dc_id' => 5,
            'date' => 6,
            'rights' => 1007,
        ];
    }
}
