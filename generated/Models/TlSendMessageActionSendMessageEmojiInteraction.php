<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;

/** Constructor model for sendMessageEmojiInteraction of SendMessageAction (crc32 25972bcb). */
final class TlSendMessageActionSendMessageEmojiInteraction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_send_message_action_send_message_emoji_interaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'emoticon' => 'string',
        'msg_id' => 'int',
    ];

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'interaction');
    }
}
