<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for encryptedChat of EncryptedChat (crc32 61f0d4c7). */
final class TlEncryptedChatEncryptedChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_encrypted_chat_encrypted_chat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'admin_id' => 'int',
        'participant_id' => 'int',
        'g_a_or_b' => 'string',
        'key_fingerprint' => 'int',
    ];
}
