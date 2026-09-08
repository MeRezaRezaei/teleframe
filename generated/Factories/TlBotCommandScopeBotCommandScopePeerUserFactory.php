<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotCommandScopeBotCommandScopePeerUser (botCommandScopePeerUser). */
final class TlBotCommandScopeBotCommandScopePeerUserFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotCommandScopeBotCommandScopePeerUser> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotCommandScopeBotCommandScopePeerUser::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'user_id' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
