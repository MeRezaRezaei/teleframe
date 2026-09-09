<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for account.resetPasswordFailedWait of account.ResetPasswordResult (crc32 e3779861). */
final class TlAccountResetPasswordResultResetPasswordFailedWait extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_reset_password_result_reset_passwo_b06ef6c44b97';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'retry_date' => 'int',
    ];
}
