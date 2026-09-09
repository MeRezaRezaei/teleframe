<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAutoDownloadSettings;

/** Constructor model for account.autoDownloadSettings of account.AutoDownloadSettings (crc32 63cacf26). */
final class TlAccountAutoDownloadSettingsAutoDownloadSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_auto_download_settings_auto_download_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function low(): BelongsTo
    {
        return $this->belongsTo(TlAutoDownloadSettings::class, 'low');
    }
    public function medium(): BelongsTo
    {
        return $this->belongsTo(TlAutoDownloadSettings::class, 'medium');
    }
    public function high(): BelongsTo
    {
        return $this->belongsTo(TlAutoDownloadSettings::class, 'high');
    }
}
