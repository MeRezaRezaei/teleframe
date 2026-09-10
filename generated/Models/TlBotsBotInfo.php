<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type bots.BotInfo (spec §4.1). */
final class TlBotsBotInfo extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bots_bot_info_bot_info';

    protected $guarded = [];
}
