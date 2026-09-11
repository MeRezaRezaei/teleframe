<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

/**
 * Telegram sticker set — tf_sticker_sets.
 *
 * PK: (id, account_id). id is the globally unique Telegram sticker set ID.
 */
class TfStickerSet extends TfModel
{
    protected $table = 'tf_sticker_sets';

    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $fillable = [
        'id', 'account_id', 'access_hash', 'title', 'short_name',
        'count', 'hash', 'installed_date', 'thumb_document_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_official' => 'boolean',
        'is_masks' => 'boolean',
        'is_emojis' => 'boolean',
    ];
}
