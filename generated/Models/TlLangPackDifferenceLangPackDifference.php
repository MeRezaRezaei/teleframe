<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlLangPackDifferenceLangPackDifferenceStrings;

/** Constructor model for langPackDifference of LangPackDifference (crc32 f385c1f6). */
final class TlLangPackDifferenceLangPackDifference extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_lang_pack_difference_lang_pack_difference';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'lang_code' => 'string',
        'from_version' => 'int',
        'version' => 'int',
    ];

    public function strings(): HasMany
    {
        return $this->tlChild(TlLangPackDifferenceLangPackDifferenceStrings::class);
    }
}
