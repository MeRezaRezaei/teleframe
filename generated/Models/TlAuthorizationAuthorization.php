<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for authorization of Authorization (crc32 ad01d61d). */
final class TlAuthorizationAuthorization extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_authorization_authorization';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_current' => 'bool',
        'official_app' => 'bool',
        'password_pending' => 'bool',
        'encrypted_requests_disabled' => 'bool',
        'call_requests_disabled' => 'bool',
        'unconfirmed' => 'bool',
        'hash' => 'int',
        'device_model' => 'string',
        'platform' => 'string',
        'system_version' => 'string',
        'api_id' => 'int',
        'app_name' => 'string',
        'app_version' => 'string',
        'date_created' => 'int',
        'date_active' => 'int',
        'ip' => 'string',
        'country' => 'string',
        'region' => 'string',
    ];
}
