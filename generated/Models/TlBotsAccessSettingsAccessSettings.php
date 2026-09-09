<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotsAccessSettingsAccessSettingsAdd_users;

/** Constructor model for bots.accessSettings of bots.AccessSettings (crc32 dd1fbf93). */
final class TlBotsAccessSettingsAccessSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bots_access_settings_access_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'restricted' => 'bool',
    ];

    public function addUsers(): HasMany
    {
        return $this->tlChild(TlBotsAccessSettingsAccessSettingsAdd_users::class);
    }
}
