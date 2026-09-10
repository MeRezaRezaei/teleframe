<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageExtendedMediaMessageExtendedMediaPreview (messageExtendedMediaPreview). */
final class TlMessageExtendedMediaMessageExtendedMediaPreviewFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageExtendedMediaMessageExtendedMediaPreview> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageExtendedMediaMessageExtendedMediaPreview::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'w' => 2,
            'h' => 3,
            'thumb' => 1004,
            'video_duration' => 5,
        ];
    }
}
