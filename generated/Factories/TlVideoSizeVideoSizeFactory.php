<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlVideoSizeVideoSize (videoSize). */
final class TlVideoSizeVideoSizeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlVideoSizeVideoSize> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlVideoSizeVideoSize::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_type' => 'type-2',
            'w' => 3,
            'h' => 4,
            'tl_size' => 5,
            'video_start_ts' => 0.6,
        ];
    }
}
