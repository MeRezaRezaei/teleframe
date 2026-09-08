<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for secureFile of SecureFile (crc32 7d09c27e). */
final class TlSecureFileSecureFile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_secure_file_secure_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'access_hash' => 'int',
        'tl_size' => 'int',
        'dc_id' => 'int',
        'date' => 'int',
        'file_hash' => 'string',
        'secret' => 'string',
    ];
}
