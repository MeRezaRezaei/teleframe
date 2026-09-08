<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for phoneCallDiscarded of PhoneCall (crc32 50ca4de1). */
final class TlPhoneCallPhoneCallDiscarded extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_phone_call_phone_call_discarded';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'need_rating' => 'bool',
        'need_debug' => 'bool',
        'video' => 'bool',
        'tl_id' => 'int',
        'reason' => 'string',
        'duration' => 'int',
    ];
}
