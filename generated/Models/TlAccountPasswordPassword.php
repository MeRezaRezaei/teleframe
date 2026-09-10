<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPasswordKdfAlgo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecurePasswordKdfAlgo;

/** Constructor model for account.password of account.Password (crc32 957b50fb). */
final class TlAccountPasswordPassword extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_password_password';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_recovery' => 'bool',
        'has_secure_values' => 'bool',
        'has_password' => 'bool',
        'srp__b' => 'string',
        'srp_id' => 'int',
        'hint' => 'string',
        'email_unconfirmed_pattern' => 'string',
        'secure_random' => 'string',
        'pending_reset_date' => 'int',
        'login_email_pattern' => 'string',
    ];

    public function currentAlgo(): BelongsTo
    {
        return $this->belongsTo(TlPasswordKdfAlgo::class, 'current_algo');
    }
    public function newAlgo(): BelongsTo
    {
        return $this->belongsTo(TlPasswordKdfAlgo::class, 'new_algo');
    }
    public function newSecureAlgo(): BelongsTo
    {
        return $this->belongsTo(TlSecurePasswordKdfAlgo::class, 'new_secure_algo');
    }
}
