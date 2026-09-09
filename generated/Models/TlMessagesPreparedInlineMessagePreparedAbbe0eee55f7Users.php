<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_messages_prepared_inline_message_prepared__86dd012cf503). */
final class TlMessagesPreparedInlineMessagePreparedAbbe0eee55f7Users extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_prepared_inline_message_prepared__86dd012cf503';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
