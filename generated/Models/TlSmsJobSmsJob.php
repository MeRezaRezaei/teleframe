<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for smsJob of SmsJob (crc32 e6a1eeb8). */
final class TlSmsJobSmsJob extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_sms_job_sms_job';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'job_id' => 'string',
        'phone_number' => 'string',
        'text' => 'string',
    ];
}
