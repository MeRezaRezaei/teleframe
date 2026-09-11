<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

/**
 * Telegram photo — tf_photos.
 *
 * PK: (id, account_id). id is the globally unique Telegram photo ID.
 * Photos are globally unique across all peers.
 */
class TfPhoto extends TfModel
{
    protected $table = 'tf_photos';

    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $fillable = [
        'id', 'account_id', 'access_hash', 'date', 'dc_id',
        'file_reference', 'width', 'height', 'size',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'has_stickers' => 'boolean',
        'file_reference' => 'binary',
    ];
}
