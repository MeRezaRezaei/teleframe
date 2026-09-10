<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRequestPeerTypeRequestPeerTypeUser (requestPeerTypeUser). */
final class TlRequestPeerTypeRequestPeerTypeUserFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeUser> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeUser::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'bot' => 1002,
            'premium' => 1003,
        ];
    }
}
