<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPrivacyRulePrivacyValueDisallowChatParticipantsChats;

/** Constructor model for privacyValueDisallowChatParticipants of PrivacyRule (crc32 41c87565). */
final class TlPrivacyRulePrivacyValueDisallowChatParticipants extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_privacy_rule_privacy_value_disallow_chat_participants';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlPrivacyRulePrivacyValueDisallowChatParticipantsChats::class);
    }
}
