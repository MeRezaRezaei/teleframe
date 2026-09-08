<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPrivacyRuleInputPrivacyValueAllowUsersUsers;

/** Constructor model for inputPrivacyValueAllowUsers of InputPrivacyRule (crc32 131cc67f). */
final class TlInputPrivacyRuleInputPrivacyValueAllowUsers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_allow_users';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlInputPrivacyRuleInputPrivacyValueAllowUsersUsers::class);
    }
}
