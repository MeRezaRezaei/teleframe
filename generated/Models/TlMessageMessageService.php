<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageService of Message (crc32 7a800e0a). */
final class TlMessageMessageService extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_message_service';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'out' => 'bool',
        'mentioned' => 'bool',
        'media_unread' => 'bool',
        'reactions_are_possible' => 'bool',
        'silent' => 'bool',
        'post' => 'bool',
        'legacy' => 'bool',
        'tl_id' => 'int',
        'from_id' => 'string',
        'peer_id' => 'string',
        'saved_peer_id' => 'string',
        'reply_to' => 'string',
        'date' => 'int',
        'action' => 'string',
        'reactions' => 'string',
        'ttl_period' => 'int',
    ];
}
