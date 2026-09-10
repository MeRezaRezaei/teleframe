<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type AttachMenuBotIcon (spec §4.1). */
final class TlAttachMenuBotIcon extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_attach_menu_bot_icon_attach_menu_bot_icon';

    protected $guarded = [];
}
