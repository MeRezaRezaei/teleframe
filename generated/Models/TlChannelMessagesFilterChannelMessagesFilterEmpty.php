<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for channelMessagesFilterEmpty of ChannelMessagesFilter (crc32 94d42ee7). */
final class TlChannelMessagesFilterChannelMessagesFilterEmpty extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_messages_filter_channel_messages_filter_empty';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
