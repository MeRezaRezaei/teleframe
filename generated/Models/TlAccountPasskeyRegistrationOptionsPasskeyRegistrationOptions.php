<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;

/** Constructor model for account.passkeyRegistrationOptions of account.PasskeyRegistrationOptions (crc32 e16b5ce1). */
final class TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptions extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_passkey_registration_options_passk_47a076e67747';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function options(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'options');
    }
}
