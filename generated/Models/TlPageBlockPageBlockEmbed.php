<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for pageBlockEmbed of PageBlock (crc32 a8718dc5). */
final class TlPageBlockPageBlockEmbed extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_embed';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'full_width' => 'bool',
        'allow_scrolling' => 'bool',
        'url' => 'string',
        'html' => 'string',
        'poster_photo_id' => 'int',
        'w' => 'int',
        'h' => 'int',
        'caption' => 'string',
    ];
}
