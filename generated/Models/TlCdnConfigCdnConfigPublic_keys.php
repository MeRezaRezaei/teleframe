<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param public_keys (table tl_cdn_config_cdn_config__public_keys). */
final class TlCdnConfigCdnConfigPublic_keys extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_cdn_config_cdn_config__public_keys';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
