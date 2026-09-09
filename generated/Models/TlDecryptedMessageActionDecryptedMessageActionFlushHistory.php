<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for decryptedMessageActionFlushHistory of DecryptedMessageAction (crc32 6719e45c). */
final class TlDecryptedMessageActionDecryptedMessageActionFlushHistory extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_action_decrypted_message_6d45161f4eb2';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
