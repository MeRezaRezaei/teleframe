<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for messageEntityFormattedDate of MessageEntity (crc32 904ac7c7). */
final class TlMessageEntityMessageEntityFormattedDate extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_entity_message_entity_formatted_date';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'relative' => 'bool',
        'short_time' => 'bool',
        'long_time' => 'bool',
        'short_date' => 'bool',
        'long_date' => 'bool',
        'day_of_week' => 'bool',
        'tl_offset' => 'int',
        'length' => 'int',
        'date' => 'int',
    ];
}
