<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInviteParticipants;

/** Constructor model for chatInvite of ChatInvite (crc32 5c9d3702). */
final class TlChatInviteChatInvite extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chat_invite_chat_invite';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'channel' => 'bool',
        'broadcast' => 'bool',
        'public' => 'bool',
        'megagroup' => 'bool',
        'request_needed' => 'bool',
        'verified' => 'bool',
        'scam' => 'bool',
        'fake' => 'bool',
        'can_refulfill_subscription' => 'bool',
        'title' => 'string',
        'about' => 'string',
        'photo' => 'string',
        'participants_count' => 'int',
        'color' => 'int',
        'subscription_pricing' => 'string',
        'subscription_form_id' => 'int',
        'bot_verification' => 'string',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlChatInviteChatInviteParticipants::class);
    }
}
