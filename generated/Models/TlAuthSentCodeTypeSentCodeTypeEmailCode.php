<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.sentCodeTypeEmailCode of auth.SentCodeType (crc32 f450f59b). */
final class TlAuthSentCodeTypeSentCodeTypeEmailCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_sent_code_type_sent_code_type_email_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'apple_signin_allowed' => 'bool',
        'google_signin_allowed' => 'bool',
        'email_pattern' => 'string',
        'length' => 'int',
        'reset_available_period' => 'int',
        'reset_pending_date' => 'int',
    ];
}
