<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param messages (table tl_messages_sponsored_messages_sponsored_mess_8fd982913adc). */
final class TlMessagesSponsoredMessagesSponsoredMessagesMessages extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_sponsored_messages_sponsored_mess_8fd982913adc';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
