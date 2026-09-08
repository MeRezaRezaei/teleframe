<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsSavedStarGiftsSavedStarGifts (payments.savedStarGifts). */
final class TlPaymentsSavedStarGiftsSavedStarGiftsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedStarGiftsSavedStarGifts> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedStarGiftsSavedStarGifts::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'count' => 2,
            'chat_notifications_enabled' => (string) new \Symfony\Component\Uid\UuidV7(),
            'next_offset' => 'next_offset-4',
        ];
    }
}
