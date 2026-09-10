<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessWorkHoursBusinessWorkHoursWeekly_open;

/** Constructor model for businessWorkHours of BusinessWorkHours (crc32 8c92b098). */
final class TlBusinessWorkHoursBusinessWorkHours extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_work_hours_business_work_hours';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'open_now' => 'bool',
        'timezone_id' => 'string',
    ];

    public function weeklyOpen(): HasMany
    {
        return $this->tlChild(TlBusinessWorkHoursBusinessWorkHoursWeekly_open::class);
    }
}
