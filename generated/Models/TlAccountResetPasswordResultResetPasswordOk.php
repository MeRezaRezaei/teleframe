<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for account.resetPasswordOk of account.ResetPasswordResult (crc32 e926d63e). */
final class TlAccountResetPasswordResultResetPasswordOk extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_reset_password_result_reset_password_ok';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
