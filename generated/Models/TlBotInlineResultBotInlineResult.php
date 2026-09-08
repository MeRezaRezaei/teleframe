<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botInlineResult of BotInlineResult (crc32 11965f3a). */
final class TlBotInlineResultBotInlineResult extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_inline_result_bot_inline_result';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'string',
        'tl_type' => 'string',
        'title' => 'string',
        'description' => 'string',
        'url' => 'string',
        'thumb' => 'string',
        'content' => 'string',
        'send_message' => 'string',
    ];
}
