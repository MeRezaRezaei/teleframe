<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.loggedOut of auth.LoggedOut (crc32 c3a2835f). */
final class TlAuthLoggedOutLoggedOut extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_logged_out_logged_out';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'future_auth_token' => 'string',
    ];
}
