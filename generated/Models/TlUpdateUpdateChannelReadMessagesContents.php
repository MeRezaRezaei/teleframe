<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelReadMessagesContentsMessages;

/** Constructor model for updateChannelReadMessagesContents of Update (crc32 25f324f7). */
final class TlUpdateUpdateChannelReadMessagesContents extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_channel_read_messages_contents';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'channel_id' => 'int',
        'top_msg_id' => 'int',
        'saved_peer_id' => 'string',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateChannelReadMessagesContentsMessages::class);
    }
}
