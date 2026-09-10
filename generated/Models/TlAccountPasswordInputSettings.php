<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type account.PasswordInputSettings (spec §4.1). */
final class TlAccountPasswordInputSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_password_input_settings_password_input_settings';

    protected $guarded = [];
}
