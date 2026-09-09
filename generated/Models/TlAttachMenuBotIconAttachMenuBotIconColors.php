<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param colors (table tl_attach_menu_bot_icon_attach_menu_bot_icon__colors). */
final class TlAttachMenuBotIconAttachMenuBotIconColors extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_attach_menu_bot_icon_attach_menu_bot_icon__colors';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
