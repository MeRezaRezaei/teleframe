<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for decryptedMessageActionRequestKey of DecryptedMessageAction (crc32 f3c9611b). */
final class TlDecryptedMessageActionDecryptedMessageActionRequestKey extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_action_decrypted_message_3b1a1a5ea7c0';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'exchange_id' => 'int',
        'g_a' => 'string',
    ];
}
