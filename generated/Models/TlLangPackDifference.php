<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateLangPack;

/** Anchor model for TL type LangPackDifference (spec §4.1). */
final class TlLangPackDifference extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_lang_pack_difference_lang_pack_difference';

    protected $guarded = [];

    public function difference(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateLangPack::class, 'difference');
    }
}
