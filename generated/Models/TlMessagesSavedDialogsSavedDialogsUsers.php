<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_messages_saved_dialogs_saved_dialogs__users). */
final class TlMessagesSavedDialogsSavedDialogsUsers extends TlAnchorModel
{
    protected $table = 'tl_messages_saved_dialogs_saved_dialogs__users';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
