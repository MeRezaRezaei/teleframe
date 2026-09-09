<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param alt_documents (table tl_message_media_message_media_document__alt_documents). */
final class TlMessageMediaMessageMediaDocumentAlt_documents extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_document__alt_documents';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
