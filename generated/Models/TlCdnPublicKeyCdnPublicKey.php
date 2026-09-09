<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for cdnPublicKey of CdnPublicKey (crc32 c982eaba). */
final class TlCdnPublicKeyCdnPublicKey extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_cdn_public_key_cdn_public_key';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'dc_id' => 'int',
        'public_key' => 'string',
    ];
}
