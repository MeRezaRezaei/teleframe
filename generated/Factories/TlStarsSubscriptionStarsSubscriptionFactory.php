<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarsSubscriptionStarsSubscription (starsSubscription). */
final class TlStarsSubscriptionStarsSubscriptionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsSubscriptionStarsSubscription> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsSubscriptionStarsSubscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'canceled' => true,
            'can_refulfill' => true,
            'missing_balance' => true,
            'bot_canceled' => true,
            'tl_id' => 'id-6',
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'until_date' => 8,
            'pricing' => (string) new \Symfony\Component\Uid\UuidV7(),
            'chat_invite_hash' => 'chat_invite_hash-10',
            'title' => 'title-11',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'invoice_slug' => 'invoice_slug-13',
        ];
    }
}
