<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWebPageWebPageNotModified (webPageNotModified). */
final class TlWebPageWebPageNotModifiedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPageNotModified> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPageNotModified::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'cached_page_views' => 2,
        ];
    }
}
