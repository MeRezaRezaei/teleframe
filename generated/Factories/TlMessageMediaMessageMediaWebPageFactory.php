<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaWebPage (messageMediaWebPage). */
final class TlMessageMediaMessageMediaWebPageFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaWebPage> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaWebPage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'force_large_media' => true,
            'force_small_media' => true,
            'manual' => true,
            'safe' => true,
            'webpage' => 1006,
        ];
    }
}
