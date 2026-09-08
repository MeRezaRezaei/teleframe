<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param photos (table tl_input_rich_message_input_rich_message__photos). */
final class TlInputRichMessageInputRichMessagePhotos extends TlAnchorModel
{
    protected $table = 'tl_input_rich_message_input_rich_message__photos';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
