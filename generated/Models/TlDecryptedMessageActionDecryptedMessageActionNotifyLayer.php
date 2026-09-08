<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for decryptedMessageActionNotifyLayer of DecryptedMessageAction (crc32 f3048883). */
final class TlDecryptedMessageActionDecryptedMessageActionNotifyLayer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_action_decrypted_message_5c7ef77b5fe3';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'layer' => 'int',
    ];
}
