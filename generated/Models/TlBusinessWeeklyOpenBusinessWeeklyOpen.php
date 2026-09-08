<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for businessWeeklyOpen of BusinessWeeklyOpen (crc32 120b1ab9). */
final class TlBusinessWeeklyOpenBusinessWeeklyOpen extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_business_weekly_open_business_weekly_open';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'start_minute' => 'int',
        'end_minute' => 'int',
    ];
}
