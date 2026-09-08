<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPrivacyRuleInputPrivacyValueAllB839cc5564e5Chats;

/** Constructor model for inputPrivacyValueAllowChatParticipants of InputPrivacyRule (crc32 840649cf). */
final class TlInputPrivacyRuleInputPrivacyValueAllowChatParticipants extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_all_b839cc5564e5';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlInputPrivacyRuleInputPrivacyValueAllB839cc5564e5Chats::class);
    }
}
