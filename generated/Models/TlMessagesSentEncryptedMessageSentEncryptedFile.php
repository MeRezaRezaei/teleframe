<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.sentEncryptedFile of messages.SentEncryptedMessage (crc32 9493ff32). */
final class TlMessagesSentEncryptedMessageSentEncryptedFile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_sent_encrypted_message_sent_encrypted_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'int',
        'file' => 'string',
    ];
}
