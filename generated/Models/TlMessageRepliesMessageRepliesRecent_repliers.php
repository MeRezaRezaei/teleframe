<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param recent_repliers (table tl_message_replies_message_replies__recent_repliers). */
final class TlMessageRepliesMessageRepliesRecent_repliers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_replies_message_replies__recent_repliers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
