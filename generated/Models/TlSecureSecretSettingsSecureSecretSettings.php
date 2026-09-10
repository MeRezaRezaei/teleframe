<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecurePasswordKdfAlgo;

/** Constructor model for secureSecretSettings of SecureSecretSettings (crc32 1527bcac). */
final class TlSecureSecretSettingsSecureSecretSettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_secret_settings_secure_secret_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'secure_secret' => 'string',
        'secure_secret_id' => 'int',
    ];

    public function secureAlgo(): BelongsTo
    {
        return $this->belongsTo(TlSecurePasswordKdfAlgo::class, 'secure_algo');
    }
}
