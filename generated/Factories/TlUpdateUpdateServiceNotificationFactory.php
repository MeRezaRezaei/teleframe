<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateServiceNotification (updateServiceNotification). */
final class TlUpdateUpdateServiceNotificationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateServiceNotification> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateServiceNotification::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'popup' => true,
            'invert_media' => true,
            'inbox_date' => 4,
            'tl_type' => 'type-5',
            'message' => 'message-6',
            'media' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
