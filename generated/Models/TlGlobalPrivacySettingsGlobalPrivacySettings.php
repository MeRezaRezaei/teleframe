<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDisallowedGiftsSettings;

/** Constructor model for globalPrivacySettings of GlobalPrivacySettings (crc32 fe41b34f). */
final class TlGlobalPrivacySettingsGlobalPrivacySettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_global_privacy_settings_global_privacy_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'archive_and_mute_new_noncontact_peers' => 'bool',
        'keep_archived_unmuted' => 'bool',
        'keep_archived_folders' => 'bool',
        'hide_read_marks' => 'bool',
        'new_noncontact_peers_require_premium' => 'bool',
        'display_gifts_button' => 'bool',
        'noncontact_peers_paid_stars' => 'int',
    ];

    public function disallowedGifts(): BelongsTo
    {
        return $this->belongsTo(TlDisallowedGiftsSettings::class, 'disallowed_gifts');
    }
}
