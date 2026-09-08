<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param exclude_users (table tl_business_bot_recipients_business_bot_recip_67c6d576f447). */
final class TlBusinessBotRecipientsBusinessBotRecipientsExclude_users extends TlAnchorModel
{
    protected $table = 'tl_business_bot_recipients_business_bot_recip_67c6d576f447';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
