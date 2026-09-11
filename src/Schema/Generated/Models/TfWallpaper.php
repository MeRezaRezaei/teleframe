<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

/**
 * Telegram wallpaper — tf_wallpapers.
 *
 * PK: (id, account_id). id is the globally unique Telegram wallpaper ID.
 */
class TfWallpaper extends TfModel
{
    protected $table = 'tf_wallpapers';

    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $fillable = [
        'id', 'account_id', 'access_hash', 'title', 'slug',
        'document_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_creator' => 'boolean',
        'is_default' => 'boolean',
        'is_pattern' => 'boolean',
        'is_dark' => 'boolean',
    ];
}
