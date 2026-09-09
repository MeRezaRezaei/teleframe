<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeaderMessageReplyHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortChatMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortMessage;

/** Anchor model for TL type MessageFwdHeader (spec §4.1). */
final class TlMessageFwdHeader extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_fwd_header';

    protected $guarded = [];

    public function fwdFrom(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'fwd_from');
    }
    public function fwdFromUpdateShortChatMessage(): HasMany
    {
        return $this->hasMany(TlUpdatesUpdateShortChatMessage::class, 'fwd_from');
    }
    public function fwdFromUpdateShortMessage(): HasMany
    {
        return $this->hasMany(TlUpdatesUpdateShortMessage::class, 'fwd_from');
    }
    public function replyFrom(): HasMany
    {
        return $this->hasMany(TlMessageReplyHeaderMessageReplyHeader::class, 'reply_from');
    }
}
