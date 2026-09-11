<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdate (domain: updates). */
final class TlUpdateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdate> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'message' => 1001,
            'pts' => 2,
            'pts_count' => 3,
            'flags' => 4,
            'final' => true,
            'timeout' => 7,
            'date' => 8,
            'seq' => 9,
            'qts' => 11,
            'unread_count' => 14,
        ];
    }
}
