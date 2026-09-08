<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaWebPageEntities;

/** Constructor model for botInlineMessageMediaWebPage of BotInlineMessage (crc32 809ad9a6). */
final class TlBotInlineMessageBotInlineMessageMediaWebPage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_inline_message_bot_inline_message_media_web_page';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'invert_media' => 'bool',
        'force_large_media' => 'bool',
        'force_small_media' => 'bool',
        'manual' => 'bool',
        'safe' => 'bool',
        'message' => 'string',
        'url' => 'string',
        'reply_markup' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlBotInlineMessageBotInlineMessageMediaWebPageEntities::class);
    }
}
