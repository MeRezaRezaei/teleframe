<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputPrivacyValueDisallowContacts of InputPrivacyRule (crc32 0ba52007). */
final class TlInputPrivacyRuleInputPrivacyValueDisallowContacts extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_dis_8a366d0a4f14';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
