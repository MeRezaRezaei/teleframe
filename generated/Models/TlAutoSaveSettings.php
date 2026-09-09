<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAutoSaveExceptionAutoSaveException;

/** Anchor model for TL type AutoSaveSettings (spec §4.1). */
final class TlAutoSaveSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_auto_save_settings';

    protected $guarded = [];

    public function broadcastsSettings(): HasMany
    {
        return $this->hasMany(TlAccountAutoSaveSettingsAutoSaveSettings::class, 'broadcasts_settings');
    }
    public function chatsSettings(): HasMany
    {
        return $this->hasMany(TlAccountAutoSaveSettingsAutoSaveSettings::class, 'chats_settings');
    }
    public function settings(): HasMany
    {
        return $this->hasMany(TlAutoSaveExceptionAutoSaveException::class, 'settings');
    }
    public function usersSettings(): HasMany
    {
        return $this->hasMany(TlAccountAutoSaveSettingsAutoSaveSettings::class, 'users_settings');
    }
}
