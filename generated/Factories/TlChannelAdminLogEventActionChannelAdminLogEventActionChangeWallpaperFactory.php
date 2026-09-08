<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaper (channelAdminLogEventActionChangeWallpaper). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaperFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaper> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaper::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prev_value' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_value' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
