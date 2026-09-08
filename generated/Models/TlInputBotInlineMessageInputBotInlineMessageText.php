<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageTextEntities;

/** Constructor model for inputBotInlineMessageText of InputBotInlineMessage (crc32 3dcd7a87). */
final class TlInputBotInlineMessageInputBotInlineMessageText extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline_message_text';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'no_webpage' => 'bool',
        'invert_media' => 'bool',
        'message' => 'string',
        'reply_markup' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlInputBotInlineMessageInputBotInlineMessageTextEntities::class);
    }
}
