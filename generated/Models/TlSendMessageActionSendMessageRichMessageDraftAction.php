<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichMessage;

/** Constructor model for sendMessageRichMessageDraftAction of SendMessageAction (crc32 a2cb24f9). */
final class TlSendMessageActionSendMessageRichMessageDraftAction extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_send_message_action_send_message_rich_mess_c3c24446ab81';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random_id' => 'int',
    ];

    public function richMessage(): BelongsTo
    {
        return $this->belongsTo(TlRichMessage::class, 'rich_message');
    }
}
