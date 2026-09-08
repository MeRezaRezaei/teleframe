<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for securePlainEmail of SecurePlainData (crc32 21ec5a5f). */
final class TlSecurePlainDataSecurePlainEmail extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_secure_plain_data_secure_plain_email';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'email' => 'string',
    ];
}
