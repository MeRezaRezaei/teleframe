<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type account.ResetPasswordResult (spec §4.1). */
final class TlAccountResetPasswordResult extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_reset_password_result';

    protected $guarded = [];
}
