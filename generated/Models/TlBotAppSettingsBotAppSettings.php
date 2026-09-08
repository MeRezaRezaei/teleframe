<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botAppSettings of BotAppSettings (crc32 c99b1950). */
final class TlBotAppSettingsBotAppSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_app_settings_bot_app_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'placeholder_path' => 'string',
        'background_color' => 'int',
        'background_dark_color' => 'int',
        'header_color' => 'int',
        'header_dark_color' => 'int',
    ];
}
