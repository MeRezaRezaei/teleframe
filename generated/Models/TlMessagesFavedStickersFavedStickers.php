<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesFavedStickersFavedStickersPacks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesFavedStickersFavedStickersStickers;

/** Constructor model for messages.favedStickers of messages.FavedStickers (crc32 2cb51097). */
final class TlMessagesFavedStickersFavedStickers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_faved_stickers_faved_stickers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function packs(): HasMany
    {
        return $this->tlChild(TlMessagesFavedStickersFavedStickersPacks::class);
    }
    public function stickers(): HasMany
    {
        return $this->tlChild(TlMessagesFavedStickersFavedStickersStickers::class);
    }
}
