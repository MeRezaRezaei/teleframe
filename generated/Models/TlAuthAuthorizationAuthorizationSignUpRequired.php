<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.authorizationSignUpRequired of auth.Authorization (crc32 44747e9a). */
final class TlAuthAuthorizationAuthorizationSignUpRequired extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_authorization_authorization_sign_up_required';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'terms_of_service' => 'string',
    ];
}
