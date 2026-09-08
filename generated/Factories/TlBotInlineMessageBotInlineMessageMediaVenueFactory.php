<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotInlineMessageBotInlineMessageMediaVenue (botInlineMessageMediaVenue). */
final class TlBotInlineMessageBotInlineMessageMediaVenueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaVenue> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaVenue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'geo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'title' => 'title-3',
            'address' => 'address-4',
            'provider' => 'provider-5',
            'venue_id' => 'venue_id-6',
            'venue_type' => 'venue_type-7',
            'reply_markup' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
