<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for sendMessageTextDraftAction of SendMessageAction (crc32 376d975c). */
final class TlSendMessageActionSendMessageTextDraftAction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_send_message_action_send_message_text_draft_action';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random_id' => 'int',
        'text' => 'string',
    ];
}
