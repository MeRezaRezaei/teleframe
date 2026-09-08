<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettingsExceptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettingsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettingsUsers;

/** Constructor model for account.autoSaveSettings of account.AutoSaveSettings (crc32 4c3e069d). */
final class TlAccountAutoSaveSettingsAutoSaveSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_auto_save_settings_auto_save_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'users_settings' => 'string',
        'chats_settings' => 'string',
        'broadcasts_settings' => 'string',
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
}
