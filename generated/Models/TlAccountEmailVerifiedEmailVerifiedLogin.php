<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCode;

/** Constructor model for account.emailVerifiedLogin of account.EmailVerified (crc32 e1bb0d61). */
final class TlAccountEmailVerifiedEmailVerifiedLogin extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_email_verified_email_verified_login';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'email' => 'string',
    ];

    public function sentCode(): BelongsTo
    {
        return $this->belongsTo(TlAuthSentCode::class, 'sent_code');
    }
}
