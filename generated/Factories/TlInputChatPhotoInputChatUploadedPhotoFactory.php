<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputChatPhotoInputChatUploadedPhoto (inputChatUploadedPhoto). */
final class TlInputChatPhotoInputChatUploadedPhotoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChatPhotoInputChatUploadedPhoto> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChatPhotoInputChatUploadedPhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'file' => 1002,
            'video' => 1003,
            'video_start_ts' => 0.4,
            'video_emoji_markup' => 1005,
        ];
    }
}
