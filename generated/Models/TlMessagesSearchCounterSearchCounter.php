<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.searchCounter of messages.SearchCounter (crc32 e844ebff). */
final class TlMessagesSearchCounterSearchCounter extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_search_counter_search_counter';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'inexact' => 'bool',
        'filter' => 'string',
        'count' => 'int',
    ];
}
