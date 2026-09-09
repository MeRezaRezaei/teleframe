<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for decryptedMessageActionAcceptKey of DecryptedMessageAction (crc32 6fe1735b). */
final class TlDecryptedMessageActionDecryptedMessageActionAcceptKey extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_action_decrypted_message_83c8b6a02d65';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'exchange_id' => 'int',
        'g_b' => 'string',
        'key_fingerprint' => 'int',
    ];
}
