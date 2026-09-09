<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerKeywordStickerKeywordKeyword;

/** Constructor model for stickerKeyword of StickerKeyword (crc32 fcfeb29c). */
final class TlStickerKeywordStickerKeyword extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_sticker_keyword_sticker_keyword';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'document_id' => 'int',
    ];

    public function keyword(): HasMany
    {
        return $this->tlChild(TlStickerKeywordStickerKeywordKeyword::class);
    }
}
