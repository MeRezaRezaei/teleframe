<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param attributes (table tl_input_media_input_media_uploaded_document__attributes). */
final class TlInputMediaInputMediaUploadedDocumentAttributes extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_uploaded_document__attributes';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
