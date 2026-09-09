<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param add_users (table tl_bots_access_settings_access_settings__add_users). */
final class TlBotsAccessSettingsAccessSettingsAdd_users extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bots_access_settings_access_settings__add_users';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
