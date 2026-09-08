<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelMessagesFilterChannelMessagesFilterRanges;

/** Constructor model for channelMessagesFilter of ChannelMessagesFilter (crc32 cd77d957). */
final class TlChannelMessagesFilterChannelMessagesFilter extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_messages_filter_channel_messages_filter';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'exclude_new_messages' => 'bool',
    ];

    public function ranges(): HasMany
    {
        return $this->tlChild(TlChannelMessagesFilterChannelMessagesFilterRanges::class);
    }
}
