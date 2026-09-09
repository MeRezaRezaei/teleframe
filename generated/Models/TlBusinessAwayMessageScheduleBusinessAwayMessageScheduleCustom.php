<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for businessAwayMessageScheduleCustom of BusinessAwayMessageSchedule (crc32 cc4d9ecc). */
final class TlBusinessAwayMessageScheduleBusinessAwayMessageScheduleCustom extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_away_message_schedule_business_aw_34632876acc8';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'start_date' => 'int',
        'end_date' => 'int',
    ];
}
