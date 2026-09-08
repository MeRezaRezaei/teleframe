<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for encryptedMessageService of EncryptedMessage (crc32 23734b06). */
final class TlEncryptedMessageEncryptedMessageService extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_encrypted_message_encrypted_message_service';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random_id' => 'int',
        'chat_id' => 'int',
        'date' => 'int',
        'bytes' => 'string',
    ];
}
