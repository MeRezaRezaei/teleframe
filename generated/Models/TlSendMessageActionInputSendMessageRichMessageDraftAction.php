<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichMessage;

/** Constructor model for inputSendMessageRichMessageDraftAction of SendMessageAction (crc32 e2b23b51). */
final class TlSendMessageActionInputSendMessageRichMessageDraftAction extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_send_message_action_input_send_message_ric_e3acb0a879b4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random_id' => 'int',
    ];

    public function richMessage(): BelongsTo
    {
        return $this->belongsTo(TlInputRichMessage::class, 'rich_message');
    }
}
