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
            'from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_peer_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reply_to' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 14,
            'action' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reactions' => (string) new \Symfony\Component\Uid\UuidV7(),
            'ttl_period' => 17,
        ];
    }
}
