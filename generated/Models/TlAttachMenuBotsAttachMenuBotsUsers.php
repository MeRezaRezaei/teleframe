<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_attach_menu_bots_attach_menu_bots__users). */
final class TlAttachMenuBotsAttachMenuBotsUsers extends TlAnchorModel
{
    protected $table = 'tl_attach_menu_bots_attach_menu_bots__users';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
