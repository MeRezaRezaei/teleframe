<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for langPackString of LangPackString (crc32 cad181f6). */
final class TlLangPackStringLangPackString extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_lang_pack_string_lang_pack_string';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_key' => 'string',
        'tl_value' => 'string',
    ];
}
