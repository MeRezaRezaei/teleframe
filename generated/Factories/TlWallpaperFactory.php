<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWallpaper (domain: wallpapers). */
final class TlWallpaperFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallpaper> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallpaper::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'flags' => 2,
            'creator' => true,
            'tl_default' => true,
            'pattern' => true,
            'dark' => true,
            'access_hash' => 1007,
            'slug' => 'slug-8',
            'document' => 1009,
            'settings' => 1010,
        ];
    }
}
