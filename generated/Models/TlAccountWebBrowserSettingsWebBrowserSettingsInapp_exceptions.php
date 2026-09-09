<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param inapp_exceptions (table tl_account_web_browser_settings_web_browser_s_4c8f4a163493). */
final class TlAccountWebBrowserSettingsWebBrowserSettingsInapp_exceptions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_web_browser_settings_web_browser_s_4c8f4a163493';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
