<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for langPackStringPluralized of LangPackString (crc32 6c47ac9f). */
final class TlLangPackStringLangPackStringPluralized extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_lang_pack_string_lang_pack_string_pluralized';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_key' => 'string',
        'zero_value' => 'string',
        'one_value' => 'string',
        'two_value' => 'string',
        'few_value' => 'string',
        'many_value' => 'string',
        'other_value' => 'string',
    ];
}
