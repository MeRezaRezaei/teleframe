<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param rules (table tl_account_privacy_rules_privacy_rules__rules). */
final class TlAccountPrivacyRulesPrivacyRulesRules extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_privacy_rules_privacy_rules__rules';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
