<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for secureData of SecureData (crc32 8aeabec3). */
final class TlSecureDataSecureData extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_data_secure_data';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'string',
        'data_hash' => 'string',
        'secret' => 'string',
    ];
}
