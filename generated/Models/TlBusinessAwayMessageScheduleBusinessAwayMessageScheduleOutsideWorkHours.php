<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for businessAwayMessageScheduleOutsideWorkHours of BusinessAwayMessageSchedule (crc32 c3f2f501). */
final class TlBusinessAwayMessageScheduleBusinessAwayMessageScheduleOutsideWorkHours extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_away_message_schedule_business_aw_b08cf0d2f8a8';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
