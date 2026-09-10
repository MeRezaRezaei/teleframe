<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsSubscriptionPricing;

/** Constructor model for chatInviteExported of ExportedChatInvite (crc32 a22cbd96). */
final class TlExportedChatInviteChatInviteExported extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_exported_chat_invite_chat_invite_exported';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'revoked' => 'bool',
        'permanent' => 'bool',
        'request_needed' => 'bool',
        'link' => 'string',
        'admin_id' => 'int',
        'date' => 'int',
        'start_date' => 'int',
        'expire_date' => 'int',
        'usage_limit' => 'int',
        'usage' => 'int',
        'requested' => 'int',
        'subscription_expired' => 'int',
        'title' => 'string',
    ];

    public function subscriptionPricing(): BelongsTo
    {
        return $this->belongsTo(TlStarsSubscriptionPricing::class, 'subscription_pricing');
    }
}
