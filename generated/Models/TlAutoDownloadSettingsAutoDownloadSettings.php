<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for autoDownloadSettings of AutoDownloadSettings (crc32 baa57628). */
final class TlAutoDownloadSettingsAutoDownloadSettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auto_download_settings_auto_download_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'disabled' => 'bool',
        'video_preload_large' => 'bool',
        'audio_preload_next' => 'bool',
        'phonecalls_less_data' => 'bool',
        'stories_preload' => 'bool',
        'photo_size_max' => 'int',
        'video_size_max' => 'int',
        'file_size_max' => 'int',
        'video_upload_maxbitrate' => 'int',
        'small_queue_active_operations_max' => 'int',
        'large_queue_active_operations_max' => 'int',
    ];
}
