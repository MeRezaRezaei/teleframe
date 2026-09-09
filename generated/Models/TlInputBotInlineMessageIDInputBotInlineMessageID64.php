<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputBotInlineMessageID64 of InputBotInlineMessageID (crc32 b6d915d7). */
final class TlInputBotInlineMessageIDInputBotInlineMessageID64 extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_i_d_input_bot_inl_d3b2ec5fd706';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'dc_id' => 'int',
        'owner_id' => 'int',
        'tl_id' => 'int',
        'access_hash' => 'int',
    ];
}
