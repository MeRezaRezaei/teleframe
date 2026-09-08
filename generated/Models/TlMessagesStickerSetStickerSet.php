<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesStickerSetStickerSetPacks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesStickerSetStickerSetKeywords;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesStickerSetStickerSetDocuments;

/** Constructor model for messages.stickerSet of messages.StickerSet (crc32 6e153f16). */
final class TlMessagesStickerSetStickerSet extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_sticker_set_sticker_set';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'set' => 'string',
    ];

    public function packs(): HasMany
    {
        return $this->tlChild(TlMessagesStickerSetStickerSetPacks::class);
    }
    public function keywords(): HasMany
    {
        return $this->tlChild(TlMessagesStickerSetStickerSetKeywords::class);
    }
    public function documents(): HasMany
    {
        return $this->tlChild(TlMessagesStickerSetStickerSetDocuments::class);
    }
}
