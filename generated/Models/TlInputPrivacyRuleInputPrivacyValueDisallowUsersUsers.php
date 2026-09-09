<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_input_privacy_rule_input_privacy_value_dis_d8b619151246). */
final class TlInputPrivacyRuleInputPrivacyValueDisallowUsersUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_privacy_rule_input_privacy_value_dis_d8b619151246';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
