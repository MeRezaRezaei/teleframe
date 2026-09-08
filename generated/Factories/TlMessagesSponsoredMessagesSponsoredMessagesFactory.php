<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesSponsoredMessagesSponsoredMessages (messages.sponsoredMessages). */
final class TlMessagesSponsoredMessagesSponsoredMessagesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSponsoredMessagesSponsoredMessages> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSponsoredMessagesSponsoredMessages::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'posts_between' => 2,
            'start_delay' => 3,
            'between_delay' => 4,
        ];
    }
}
