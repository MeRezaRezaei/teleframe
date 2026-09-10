<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;

/** Constructor model for auth.authorization of auth.Authorization (crc32 2ea2c0d4). */
final class TlAuthAuthorizationAuthorization extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_authorization_authorization';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'setup_password_required' => 'bool',
        'otherwise_relogin_days' => 'int',
        'tmp_sessions' => 'int',
        'future_auth_token' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TlUser::class, 'tl_user');
    }
}
