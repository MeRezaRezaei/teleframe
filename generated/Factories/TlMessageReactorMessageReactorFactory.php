<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageReactorMessageReactor (messageReactor). */
final class TlMessageReactorMessageReactorFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReactorMessageReactor> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReactorMessageReactor::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'top' => true,
            'my' => true,
            'anonymous' => true,
            'peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'count' => 6,
        ];
    }
}
