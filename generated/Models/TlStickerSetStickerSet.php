<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetStickerSetThumbs;

/** Constructor model for stickerSet of StickerSet (crc32 2dd14edc). */
final class TlStickerSetStickerSet extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_sticker_set_sticker_set';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'archived' => 'bool',
        'official' => 'bool',
        'masks' => 'bool',
        'emojis' => 'bool',
        'text_color' => 'bool',
        'channel_emoji_status' => 'bool',
        'creator' => 'bool',
        'installed_date' => 'int',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'title' => 'string',
        'short_name' => 'string',
        'thumb_dc_id' => 'int',
        'thumb_version' => 'int',
        'thumb_document_id' => 'int',
        'count' => 'int',
        'hash' => 'int',
    ];

    public function thumbs(): HasMany
    {
        return $this->tlChild(TlStickerSetStickerSetThumbs::class);
    }
}
