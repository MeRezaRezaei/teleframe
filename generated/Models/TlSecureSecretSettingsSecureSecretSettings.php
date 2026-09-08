<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for secureSecretSettings of SecureSecretSettings (crc32 1527bcac). */
final class TlSecureSecretSettingsSecureSecretSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_secure_secret_settings_secure_secret_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'secure_algo' => 'string',
        'secure_secret' => 'string',
        'secure_secret_id' => 'int',
    ];
}
