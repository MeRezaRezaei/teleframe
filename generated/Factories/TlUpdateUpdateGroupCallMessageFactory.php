<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateGroupCallMessage (updateGroupCallMessage). */
final class TlUpdateUpdateGroupCallMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'call' => 1001,
            'message' => 1002,
        ];
    }
}
