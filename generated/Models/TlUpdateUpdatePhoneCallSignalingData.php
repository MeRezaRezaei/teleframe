<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for updatePhoneCallSignalingData of Update (crc32 2661bf09). */
final class TlUpdateUpdatePhoneCallSignalingData extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_phone_call_signaling_data';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'phone_call_id' => 'int',
        'data' => 'string',
    ];
}
