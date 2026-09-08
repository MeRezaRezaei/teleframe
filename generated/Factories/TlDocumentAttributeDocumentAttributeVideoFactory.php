<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDocumentAttributeDocumentAttributeVideo (documentAttributeVideo). */
final class TlDocumentAttributeDocumentAttributeVideoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentAttributeDocumentAttributeVideo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentAttributeDocumentAttributeVideo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'round_message' => true,
            'supports_streaming' => true,
            'nosound' => true,
            'duration' => 0.5,
            'w' => 6,
            'h' => 7,
            'preload_prefix_size' => 8,
            'video_start_ts' => 0.9,
            'video_codec' => 'video_codec-10',
        ];
    }
}
