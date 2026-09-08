<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSmsjobsStatusStatus (smsjobs.status). */
final class TlSmsjobsStatusStatusFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSmsjobsStatusStatus> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSmsjobsStatusStatus::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'allow_international' => true,
            'recent_sent' => 3,
            'recent_since' => 4,
            'recent_remains' => 5,
            'total_sent' => 6,
            'total_since' => 7,
            'last_gift_slug' => 'last_gift_slug-8',
            'terms_url' => 'terms_url-9',
        ];
    }
}
