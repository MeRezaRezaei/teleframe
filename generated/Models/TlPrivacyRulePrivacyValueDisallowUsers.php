<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPrivacyRulePrivacyValueDisallowUsersUsers;

/** Constructor model for privacyValueDisallowUsers of PrivacyRule (crc32 e4621141). */
final class TlPrivacyRulePrivacyValueDisallowUsers extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_privacy_rule_privacy_value_disallow_users';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPrivacyRulePrivacyValueDisallowUsersUsers::class);
    }
}
