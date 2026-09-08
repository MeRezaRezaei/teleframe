<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for decryptedMessageActionCommitKey of DecryptedMessageAction (crc32 ec2e0b9b). */
final class TlDecryptedMessageActionDecryptedMessageActionCommitKey extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_action_decrypted_message_ef9eacbe15fe';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'exchange_id' => 'int',
        'key_fingerprint' => 'int',
    ];
}
