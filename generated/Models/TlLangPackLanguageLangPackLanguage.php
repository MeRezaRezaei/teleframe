<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for langPackLanguage of LangPackLanguage (crc32 eeca5ce3). */
final class TlLangPackLanguageLangPackLanguage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_lang_pack_language_lang_pack_language';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'official' => 'bool',
        'rtl' => 'bool',
        'beta' => 'bool',
        'name' => 'string',
        'native_name' => 'string',
        'lang_code' => 'string',
        'base_lang_code' => 'string',
        'plural_code' => 'string',
        'strings_count' => 'int',
        'translated_count' => 'int',
        'translations_url' => 'string',
    ];
}
