<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for decryptedMessageActionNoop of DecryptedMessageAction (crc32 a82fdd63). */
final class TlDecryptedMessageActionDecryptedMessageActionNoop extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_action_decrypted_message_action_noop';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
