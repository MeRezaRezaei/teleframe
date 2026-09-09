<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for auth.passwordRecovery of auth.PasswordRecovery (crc32 137948a5). */
final class TlAuthPasswordRecoveryPasswordRecovery extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_password_recovery_password_recovery';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'email_pattern' => 'string',
    ];
}
