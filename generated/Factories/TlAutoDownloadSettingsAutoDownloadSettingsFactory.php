<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAutoDownloadSettingsAutoDownloadSettings (autoDownloadSettings). */
final class TlAutoDownloadSettingsAutoDownloadSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAutoDownloadSettingsAutoDownloadSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAutoDownloadSettingsAutoDownloadSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'disabled' => true,
            'video_preload_large' => true,
            'audio_preload_next' => true,
            'phonecalls_less_data' => true,
            'stories_preload' => true,
            'photo_size_max' => 7,
            'video_size_max' => 1008,
            'file_size_max' => 1009,
            'video_upload_maxbitrate' => 10,
            'small_queue_active_operations_max' => 11,
            'large_queue_active_operations_max' => 12,
        ];
    }
}
