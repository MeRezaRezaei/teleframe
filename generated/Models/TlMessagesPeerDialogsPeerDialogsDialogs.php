<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param dialogs (table tl_messages_peer_dialogs_peer_dialogs__dialogs). */
final class TlMessagesPeerDialogsPeerDialogsDialogs extends TlAnchorModel
{
    protected $table = 'tl_messages_peer_dialogs_peer_dialogs__dialogs';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
