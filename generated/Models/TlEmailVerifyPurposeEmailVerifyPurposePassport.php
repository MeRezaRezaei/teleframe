<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for emailVerifyPurposePassport of EmailVerifyPurpose (crc32 bbf51685). */
final class TlEmailVerifyPurposeEmailVerifyPurposePassport extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_email_verify_purpose_email_verify_purpose_passport';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
