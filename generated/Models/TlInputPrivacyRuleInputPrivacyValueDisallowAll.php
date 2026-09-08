<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPrivacyValueDisallowAll of InputPrivacyRule (crc32 d66b66c9). */
final class TlInputPrivacyRuleInputPrivacyValueDisallowAll extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_disallow_all';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
