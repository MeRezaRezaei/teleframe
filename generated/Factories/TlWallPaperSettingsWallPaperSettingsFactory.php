<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlWallPaperSettingsWallPaperSettings (wallPaperSettings). */
final class TlWallPaperSettingsWallPaperSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperSettingsWallPaperSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperSettingsWallPaperSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'blur' => true,
            'motion' => true,
            'background_color' => 4,
            'second_background_color' => 5,
            'third_background_color' => 6,
            'fourth_background_color' => 7,
            'intensity' => 8,
            'rotation' => 9,
            'emoticon' => 'emoticon-10',
        ];
    }
}
