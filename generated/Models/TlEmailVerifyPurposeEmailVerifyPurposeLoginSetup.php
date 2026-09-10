<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for emailVerifyPurposeLoginSetup of EmailVerifyPurpose (crc32 4345be73). */
final class TlEmailVerifyPurposeEmailVerifyPurposeLoginSetup extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_email_verify_purpose_email_verify_purpose_login_setup';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'phone_number' => 'string',
        'phone_code_hash' => 'string',
    ];
}
