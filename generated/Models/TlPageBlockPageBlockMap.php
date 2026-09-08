<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for pageBlockMap of PageBlock (crc32 a44f3ef6). */
final class TlPageBlockPageBlockMap extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_map';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'geo' => 'string',
        'zoom' => 'int',
        'w' => 'int',
        'h' => 'int',
        'caption' => 'string',
    ];
}
