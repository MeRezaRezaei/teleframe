<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateEditChannelMessage (updateEditChannelMessage). */
final class TlUpdateUpdateEditChannelMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEditChannelMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEditChannelMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'message' => 1001,
            'pts' => 2,
            'pts_count' => 3,
        ];
    }
}
