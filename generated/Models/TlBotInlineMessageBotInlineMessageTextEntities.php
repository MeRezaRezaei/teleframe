<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param entities (table tl_bot_inline_message_bot_inline_message_text__entities). */
final class TlBotInlineMessageBotInlineMessageTextEntities extends TlAnchorModel
{
    protected $table = 'tl_bot_inline_message_bot_inline_message_text__entities';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
