<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param entities (table tl_input_bot_inline_message_input_bot_inline__24c36bb1c4b3). */
final class TlInputBotInlineMessageInputBotInlineMessageTextEntities extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline__24c36bb1c4b3';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
