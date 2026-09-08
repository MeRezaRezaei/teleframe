<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputMediaAreaChannelPost of MediaArea (crc32 2271f2bf). */
final class TlMediaAreaInputMediaAreaChannelPost extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_media_area_input_media_area_channel_post';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'coordinates' => 'string',
        'channel' => 'string',
        'msg_id' => 'int',
    ];
}
