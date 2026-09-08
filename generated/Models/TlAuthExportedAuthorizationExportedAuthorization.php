<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.exportedAuthorization of auth.ExportedAuthorization (crc32 b434e2b8). */
final class TlAuthExportedAuthorizationExportedAuthorization extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_exported_authorization_exported_authorization';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'bytes' => 'string',
    ];
}
