<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputAppEventInputAppEvent (inputAppEvent). */
final class TlInputAppEventInputAppEventFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputAppEventInputAppEvent> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputAppEventInputAppEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'time' => 0.1,
            'tl_type' => 'type-2',
            'peer' => 1003,
            'data' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
