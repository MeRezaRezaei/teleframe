<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for account.tmpPassword of account.TmpPassword (crc32 db64fd34). */
final class TlAccountTmpPasswordTmpPassword extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_tmp_password_tmp_password';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tmp_password' => 'string',
        'valid_until' => 'int',
    ];
}
