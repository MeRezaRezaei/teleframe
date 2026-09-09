<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param strings (table tl_lang_pack_difference_lang_pack_difference__strings). */
final class TlLangPackDifferenceLangPackDifferenceStrings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_lang_pack_difference_lang_pack_difference__strings';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
