<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPrivacyRuleInputPrivacyValueDis92dd14476e43Chats;

/** Constructor model for inputPrivacyValueDisallowChatParticipants of InputPrivacyRule (crc32 e94f0f86). */
final class TlInputPrivacyRuleInputPrivacyValueDisallowChatParticipants extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_dis_92dd14476e43';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlInputPrivacyRuleInputPrivacyValueDis92dd14476e43Chats::class);
    }
}
