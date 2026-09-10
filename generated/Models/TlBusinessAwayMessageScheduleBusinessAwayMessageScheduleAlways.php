<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for businessAwayMessageScheduleAlways of BusinessAwayMessageSchedule (crc32 c9b9e2b9). */
final class TlBusinessAwayMessageScheduleBusinessAwayMessageScheduleAlways extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_away_message_schedule_business_aw_c4687f6e65da';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
