<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for wallPaperSettings of WallPaperSettings (crc32 372efcd0). */
final class TlWallPaperSettingsWallPaperSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_wall_paper_settings_wall_paper_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'blur' => 'bool',
        'motion' => 'bool',
        'background_color' => 'int',
        'second_background_color' => 'int',
        'third_background_color' => 'int',
        'fourth_background_color' => 'int',
        'intensity' => 'int',
        'rotation' => 'int',
        'emoticon' => 'string',
    ];
}
