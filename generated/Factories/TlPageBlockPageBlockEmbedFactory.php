<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockEmbed (pageBlockEmbed). */
final class TlPageBlockPageBlockEmbedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockEmbed> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockEmbed::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'full_width' => true,
            'allow_scrolling' => true,
            'url' => 'url-4',
            'html' => 'html-5',
            'poster_photo_id' => 1006,
            'w' => 7,
            'h' => 8,
            'caption' => 1009,
        ];
    }
}
