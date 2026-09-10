<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageViewsMessageViews (messageViews). */
final class TlMessageViewsMessageViewsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageViewsMessageViews> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageViewsMessageViews::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'views' => 2,
            'forwards' => 3,
            'replies' => 1004,
        ];
    }
}
