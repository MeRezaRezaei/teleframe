<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for privacyValueDisallowAll of PrivacyRule (crc32 8b73e763). */
final class TlPrivacyRulePrivacyValueDisallowAll extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_privacy_rule_privacy_value_disallow_all';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
