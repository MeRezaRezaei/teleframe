<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateEditMessage (updateEditMessage). */
final class TlUpdateUpdateEditMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEditMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEditMessage::class;

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
