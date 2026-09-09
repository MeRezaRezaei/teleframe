<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for groupCallStreamChannel of GroupCallStreamChannel (crc32 80eb48af). */
final class TlGroupCallStreamChannelGroupCallStreamChannel extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_group_call_stream_channel_group_call_stream_channel';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'channel' => 'int',
        'scale' => 'int',
        'last_timestamp_ms' => 'int',
    ];
}
