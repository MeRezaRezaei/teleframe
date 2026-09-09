<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param events (table tl_channels_admin_log_results_admin_log_results__events). */
final class TlChannelsAdminLogResultsAdminLogResultsEvents extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channels_admin_log_results_admin_log_results__events';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
