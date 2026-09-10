<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockVideo (pageBlockVideo). */
final class TlPageBlockPageBlockVideoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockVideo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockVideo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'autoplay' => true,
            'loop' => true,
            'spoiler' => true,
            'video_id' => 1005,
            'caption' => 1006,
        ];
    }
}
