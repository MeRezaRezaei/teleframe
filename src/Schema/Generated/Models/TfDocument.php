<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

/**
 * Telegram document (file) — tf_documents.
 *
 * PK: (id, account_id). id is the globally unique Telegram document ID.
 * Documents are globally unique across all peers.
 */
class TfDocument extends TfModel
{
    protected $table = 'tf_documents';

    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $fillable = [
        'id', 'account_id', 'access_hash', 'date', 'title',
        'mime_type', 'size', 'dc_id', 'file_reference',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'file_reference' => 'binary',
    ];
}
