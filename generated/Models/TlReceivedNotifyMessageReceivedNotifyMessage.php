<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for receivedNotifyMessage of ReceivedNotifyMessage (crc32 a384b779). */
final class TlReceivedNotifyMessageReceivedNotifyMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_received_notify_message_received_notify_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'flags' => 'int',
    ];
}
