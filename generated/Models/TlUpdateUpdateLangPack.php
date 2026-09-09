<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlLangPackDifference;

/** Constructor model for updateLangPack of Update (crc32 56022f4d). */
final class TlUpdateUpdateLangPack extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_lang_pack';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function difference(): BelongsTo
    {
        return $this->belongsTo(TlLangPackDifference::class, 'difference');
    }
}
