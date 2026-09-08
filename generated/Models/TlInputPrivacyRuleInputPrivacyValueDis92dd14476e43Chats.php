<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param chats (table tl_input_privacy_rule_input_privacy_value_dis_bb41d2b871d8). */
final class TlInputPrivacyRuleInputPrivacyValueDis92dd14476e43Chats extends TlAnchorModel
{
    protected $table = 'tl_input_privacy_rule_input_privacy_value_dis_bb41d2b871d8';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
