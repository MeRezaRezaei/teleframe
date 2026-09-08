<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param messages (table tl_messages_affected_found_messages_affected__84127c85d979). */
final class TlMessagesAffectedFoundMessagesAffectedD0b5b58c5216Messages extends TlAnchorModel
{
    protected $table = 'tl_messages_affected_found_messages_affected__84127c85d979';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
