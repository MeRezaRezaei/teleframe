<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for updateNewChannelMessage of Update (crc32 62ba04d9). */
final class TlUpdateUpdateNewChannelMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_new_channel_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'message' => 'string',
        'pts' => 'int',
        'pts_count' => 'int',
    ];
}
