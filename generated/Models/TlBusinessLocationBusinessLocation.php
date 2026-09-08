<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for businessLocation of BusinessLocation (crc32 ac5c1af7). */
final class TlBusinessLocationBusinessLocation extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_business_location_business_location';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'geo_point' => 'string',
        'address' => 'string',
    ];
}
