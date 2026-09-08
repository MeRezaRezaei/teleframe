<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param match_codes (table tl_url_auth_result_url_auth_result_request__match_codes). */
final class TlUrlAuthResultUrlAuthResultRequestMatch_codes extends TlAnchorModel
{
    protected $table = 'tl_url_auth_result_url_auth_result_request__match_codes';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
