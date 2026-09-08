<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param pinned_peers (table tl_dialog_filter_dialog_filter_chatlist__pinned_peers). */
final class TlDialogFilterDialogFilterChatlistPinned_peers extends TlAnchorModel
{
    protected $table = 'tl_dialog_filter_dialog_filter_chatlist__pinned_peers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
