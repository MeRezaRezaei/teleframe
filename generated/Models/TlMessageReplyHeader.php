<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageService;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortChatMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortMessage;

/** Anchor model for TL type MessageReplyHeader (spec §4.1). */
final class TlMessageReplyHeader extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_reply_header';

    protected $guarded = [];

    public function replyTo(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'reply_to');
    }
    public function replyToMessageService(): HasMany
    {
        return $this->hasMany(TlMessageMessageService::class, 'reply_to');
    }
    public function replyToUpdateShortChatMessage(): HasMany
    {
        return $this->hasMany(TlUpdatesUpdateShortChatMessage::class, 'reply_to');
    }
    public function replyToUpdateShortMessage(): HasMany
    {
        return $this->hasMany(TlUpdatesUpdateShortMessage::class, 'reply_to');
    }
}
