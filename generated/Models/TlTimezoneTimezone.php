<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for timezone of Timezone (crc32 ff9289f5). */
final class TlTimezoneTimezone extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_timezone_timezone';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'string',
        'name' => 'string',
        'utc_offset' => 'int',
    ];
}
