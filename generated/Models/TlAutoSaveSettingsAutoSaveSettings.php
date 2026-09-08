<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for autoSaveSettings of AutoSaveSettings (crc32 c84834ce). */
final class TlAutoSaveSettingsAutoSaveSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auto_save_settings_auto_save_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'photos' => 'bool',
        'videos' => 'bool',
        'video_max_size' => 'int',
    ];
}
