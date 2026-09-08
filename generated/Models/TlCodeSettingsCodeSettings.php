<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlCodeSettingsCodeSettingsLogout_tokens;

/** Constructor model for codeSettings of CodeSettings (crc32 ad253d78). */
final class TlCodeSettingsCodeSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_code_settings_code_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'allow_flashcall' => 'bool',
        'current_number' => 'bool',
        'allow_app_hash' => 'bool',
        'allow_missed_call' => 'bool',
        'allow_firebase' => 'bool',
        'unknown_number' => 'bool',
        'token' => 'string',
        'app_sandbox' => 'string',
    ];

    public function logoutTokens(): HasMany
    {
        return $this->tlChild(TlCodeSettingsCodeSettingsLogout_tokens::class);
    }
}
