<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMediaAreaInputMediaAreaChannelPost (inputMediaAreaChannelPost). */
final class TlMediaAreaInputMediaAreaChannelPostFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaInputMediaAreaChannelPost> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaInputMediaAreaChannelPost::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'coordinates' => 1001,
            'channel' => 1002,
            'msg_id' => 3,
        ];
    }
}
