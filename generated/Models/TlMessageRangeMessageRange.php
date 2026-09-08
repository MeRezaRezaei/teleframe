<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageRange of MessageRange (crc32 0ae30253). */
final class TlMessageRangeMessageRange extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_range_message_range';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'min_id' => 'int',
        'max_id' => 'int',
    ];
}
