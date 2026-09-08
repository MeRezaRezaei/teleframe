<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputPeerInputPeerUser (inputPeerUser). */
final class TlInputPeerInputPeerUserFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerInputPeerUser> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerInputPeerUser::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'access_hash' => 1002,
        ];
    }
}
