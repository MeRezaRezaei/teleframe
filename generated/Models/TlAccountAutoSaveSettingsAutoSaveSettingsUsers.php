<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_account_auto_save_settings_auto_save_settings__users). */
final class TlAccountAutoSaveSettingsAutoSaveSettingsUsers extends TlAnchorModel
{
    protected $table = 'tl_account_auto_save_settings_auto_save_settings__users';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
