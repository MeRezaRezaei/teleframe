<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSuggestedPostSuggestedPost (suggestedPost). */
final class TlSuggestedPostSuggestedPostFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSuggestedPostSuggestedPost> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSuggestedPostSuggestedPost::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'accepted' => true,
            'rejected' => true,
            'price' => 1004,
            'schedule_date' => 5,
        ];
    }
}
