<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for emailVerifyPurposeLoginChange of EmailVerifyPurpose (crc32 527d22eb). */
final class TlEmailVerifyPurposeEmailVerifyPurposeLoginChange extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_email_verify_purpose_email_verify_purpose_login_change';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
