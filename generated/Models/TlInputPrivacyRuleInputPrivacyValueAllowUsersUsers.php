<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_input_privacy_rule_input_privacy_value_all_71681af9150e). */
final class TlInputPrivacyRuleInputPrivacyValueAllowUsersUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_all_71681af9150e';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
