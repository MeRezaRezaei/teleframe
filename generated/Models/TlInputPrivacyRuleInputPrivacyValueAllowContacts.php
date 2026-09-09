<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputPrivacyValueAllowContacts of InputPrivacyRule (crc32 0d09e07b). */
final class TlInputPrivacyRuleInputPrivacyValueAllowContacts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_allow_contacts';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
