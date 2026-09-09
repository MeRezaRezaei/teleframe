<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotVerification;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInviteParticipants;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsSubscriptionPricing;

/** Constructor model for chatInvite of ChatInvite (crc32 5c9d3702). */
final class TlChatInviteChatInvite extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

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
        'participants_count' => 'int',
        'color' => 'int',
        'subscription_form_id' => 'int',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlChatInviteChatInviteParticipants::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'photo');
    }
    public function subscriptionPricing(): BelongsTo
    {
        return $this->belongsTo(TlStarsSubscriptionPricing::class, 'subscription_pricing');
    }
    public function botVerification(): BelongsTo
    {
        return $this->belongsTo(TlBotVerification::class, 'bot_verification');
    }
}
