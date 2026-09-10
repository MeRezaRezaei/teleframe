<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMediaInputMediaUploadedDocument (inputMediaUploadedDocument). */
final class TlInputMediaInputMediaUploadedDocumentFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocument> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocument::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'nosound_video' => true,
            'force_file' => true,
            'spoiler' => true,
            'file' => 1005,
            'thumb' => 1006,
            'mime_type' => 'mime_type-7',
            'video_cover' => 1008,
            'video_timestamp' => 9,
            'ttl_seconds' => 10,
        ];
    }
}
