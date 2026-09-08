<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param sent_messages (table tl_update_update_delete_scheduled_messages__sent_messages). */
final class TlUpdateUpdateDeleteScheduledMessagesSent_messages extends TlAnchorModel
{
    protected $table = 'tl_update_update_delete_scheduled_messages__sent_messages';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
