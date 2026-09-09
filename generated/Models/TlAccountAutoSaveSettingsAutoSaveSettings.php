<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettingsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettingsExceptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettingsUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAutoSaveSettings;

/** Constructor model for account.autoSaveSettings of account.AutoSaveSettings (crc32 4c3e069d). */
final class TlAccountAutoSaveSettingsAutoSaveSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_auto_save_settings_auto_save_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function exceptions(): HasMany
    {
        return $this->tlChild(TlAccountAutoSaveSettingsAutoSaveSettingsExceptions::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlAccountAutoSaveSettingsAutoSaveSettingsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAccountAutoSaveSettingsAutoSaveSettingsUsers::class);
    }

    public function usersSettings(): BelongsTo
    {
        return $this->belongsTo(TlAutoSaveSettings::class, 'users_settings');
    }
    public function chatsSettings(): BelongsTo
    {
        return $this->belongsTo(TlAutoSaveSettings::class, 'chats_settings');
    }
    public function broadcastsSettings(): BelongsTo
    {
        return $this->belongsTo(TlAutoSaveSettings::class, 'broadcasts_settings');
    }
}
