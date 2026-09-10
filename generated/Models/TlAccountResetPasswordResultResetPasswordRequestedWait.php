<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for account.resetPasswordRequestedWait of account.ResetPasswordResult (crc32 e9effc7d). */
final class TlAccountResetPasswordResultResetPasswordRequestedWait extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_reset_password_result_reset_passwo_87f50999585d';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'until_date' => 'int',
    ];
}
