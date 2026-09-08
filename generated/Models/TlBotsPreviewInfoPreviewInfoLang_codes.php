<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param lang_codes (table tl_bots_preview_info_preview_info__lang_codes). */
final class TlBotsPreviewInfoPreviewInfoLang_codes extends TlAnchorModel
{
    protected $table = 'tl_bots_preview_info_preview_info__lang_codes';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
