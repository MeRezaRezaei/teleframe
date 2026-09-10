<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMessageService (messageService). */
final class TlMessageMessageServiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageService> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageService::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'out' => true,
            'mentioned' => true,
            'media_unread' => true,
            'reactions_are_possible' => true,
            'silent' => true,
            'post' => true,
            'legacy' => true,
            'tl_id' => 9,
            'from_id' => 1010,
            'peer_id' => 1011,
            'saved_peer_id' => 1012,
            'reply_to' => 1013,
            'date' => 14,
            'action' => 1015,
            'reactions' => 1016,
            'ttl_period' => 17,
        ];
    }
}
