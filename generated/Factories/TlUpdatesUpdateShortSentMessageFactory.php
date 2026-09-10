<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdatesUpdateShortSentMessage (updateShortSentMessage). */
final class TlUpdatesUpdateShortSentMessageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortSentMessage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortSentMessage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'out' => true,
            'tl_id' => 3,
            'pts' => 4,
            'pts_count' => 5,
            'date' => 6,
            'media' => 1007,
            'ttl_period' => 8,
        ];
    }
}
