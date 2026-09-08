<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.loginTokenMigrateTo of auth.LoginToken (crc32 068e9916). */
final class TlAuthLoginTokenLoginTokenMigrateTo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_login_token_login_token_migrate_to';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'dc_id' => 'int',
        'token' => 'string',
    ];
}
