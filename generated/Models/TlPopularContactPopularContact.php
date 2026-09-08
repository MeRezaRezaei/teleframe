<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for popularContact of PopularContact (crc32 5ce14175). */
final class TlPopularContactPopularContact extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_popular_contact_popular_contact';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'client_id' => 'int',
        'importers' => 'int',
    ];
}
