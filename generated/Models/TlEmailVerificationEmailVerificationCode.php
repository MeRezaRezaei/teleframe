<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for emailVerificationCode of EmailVerification (crc32 922e55a9). */
final class TlEmailVerificationEmailVerificationCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_email_verification_email_verification_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'code' => 'string',
    ];
}
