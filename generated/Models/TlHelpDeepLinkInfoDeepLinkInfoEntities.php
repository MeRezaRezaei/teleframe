<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param entities (table tl_help_deep_link_info_deep_link_info__entities). */
final class TlHelpDeepLinkInfoDeepLinkInfoEntities extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_help_deep_link_info_deep_link_info__entities';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
