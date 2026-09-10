<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGroupCallMessageGroupCallMessage (groupCallMessage). */
final class TlGroupCallMessageGroupCallMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallMessageGroupCallMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallMessageGroupCallMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'from_admin' => true,
            'tl_id' => 3,
            'from_id' => 1004,
            'date' => 5,
            'message' => 1006,
            'paid_message_stars' => 1007,
        ];
    }
}
