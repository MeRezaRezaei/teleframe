<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for bind_auth_key_inner of BindAuthKeyInner (crc32 75a3f765). */
final class TlBindAuthKeyInnerBindAuthKeyInner extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bind_auth_key_inner_bind_auth_key_inner';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'nonce' => 'int',
        'temp_auth_key_id' => 'int',
        'perm_auth_key_id' => 'int',
        'temp_session_id' => 'int',
        'expires_at' => 'int',
    ];
}
