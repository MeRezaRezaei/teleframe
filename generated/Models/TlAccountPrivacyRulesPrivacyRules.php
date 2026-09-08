<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPrivacyRulesPrivacyRulesRules;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPrivacyRulesPrivacyRulesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPrivacyRulesPrivacyRulesUsers;

/** Constructor model for account.privacyRules of account.PrivacyRules (crc32 50a04e45). */
final class TlAccountPrivacyRulesPrivacyRules extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_privacy_rules_privacy_rules';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function rules(): HasMany
    {
        return $this->tlChild(TlAccountPrivacyRulesPrivacyRulesRules::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlAccountPrivacyRulesPrivacyRulesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAccountPrivacyRulesPrivacyRulesUsers::class);
    }
}
