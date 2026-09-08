<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPrivacyRuleInputPrivacyValueDisallowUsersUsers;

/** Constructor model for inputPrivacyValueDisallowUsers of InputPrivacyRule (crc32 90110467). */
final class TlInputPrivacyRuleInputPrivacyValueDisallowUsers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_disallow_users';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlInputPrivacyRuleInputPrivacyValueDisallowUsersUsers::class);
    }
}
