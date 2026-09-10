<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesBotResultsBotResults (messages.botResults). */
final class TlMessagesBotResultsBotResultsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotResultsBotResults> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotResultsBotResults::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'gallery' => true,
            'query_id' => 1003,
            'next_offset' => 'next_offset-4',
            'switch_pm' => 1005,
            'switch_webview' => 1006,
            'cache_time' => 7,
        ];
    }
}
