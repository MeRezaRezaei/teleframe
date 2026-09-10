<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for auth.sentCodeTypeSetUpEmailRequired of auth.SentCodeType (crc32 a5491dea). */
final class TlAuthSentCodeTypeSentCodeTypeSetUpEmailRequired extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_sent_code_type_sent_code_type_set_up__b88c5cb2dd2c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'apple_signin_allowed' => 'bool',
        'google_signin_allowed' => 'bool',
    ];
}
