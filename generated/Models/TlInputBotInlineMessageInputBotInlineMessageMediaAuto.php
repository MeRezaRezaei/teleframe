<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInline93d9b4b179e4Entities;

/** Constructor model for inputBotInlineMessageMediaAuto of InputBotInlineMessage (crc32 3380c786). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaAuto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'invert_media' => 'bool',
        'message' => 'string',
        'reply_markup' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlInputBotInlineMessageInputBotInline93d9b4b179e4Entities::class);
    }
}
