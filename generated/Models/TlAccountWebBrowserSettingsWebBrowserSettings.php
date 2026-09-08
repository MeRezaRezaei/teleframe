<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountWebBrowserSettingsWebBrowserSettingsExternal_exceptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountWebBrowserSettingsWebBrowserSettingsInapp_exceptions;

/** Constructor model for account.webBrowserSettings of account.WebBrowserSettings (crc32 79eb8cb3). */
final class TlAccountWebBrowserSettingsWebBrowserSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_web_browser_settings_web_browser_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'open_external_browser' => 'bool',
        'display_close_button' => 'bool',
        'hash' => 'int',
    ];

    public function externalExceptions(): HasMany
    {
        return $this->tlChild(TlAccountWebBrowserSettingsWebBrowserSettingsExternal_exceptions::class);
    }
    public function inappExceptions(): HasMany
    {
        return $this->tlChild(TlAccountWebBrowserSettingsWebBrowserSettingsInapp_exceptions::class);
    }
}
