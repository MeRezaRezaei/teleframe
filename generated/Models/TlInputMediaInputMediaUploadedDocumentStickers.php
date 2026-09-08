<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param stickers (table tl_input_media_input_media_uploaded_document__stickers). */
final class TlInputMediaInputMediaUploadedDocumentStickers extends TlAnchorModel
{
    protected $table = 'tl_input_media_input_media_uploaded_document__stickers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
