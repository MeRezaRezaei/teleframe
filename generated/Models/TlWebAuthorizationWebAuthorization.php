<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for webAuthorization of WebAuthorization (crc32 a6f8f452). */
final class TlWebAuthorizationWebAuthorization extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_authorization_web_authorization';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
        'bot_id' => 'int',
        'domain' => 'string',
        'browser' => 'string',
        'platform' => 'string',
        'date_created' => 'int',
        'date_active' => 'int',
        'ip' => 'string',
        'region' => 'string',
    ];
}
