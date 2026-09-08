<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for decryptedMessageMediaWebPage of DecryptedMessageMedia (crc32 e50511d8). */
final class TlDecryptedMessageMediaDecryptedMessageMediaWebPage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_media_decrypted_message__8fed41c9f5cf';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
    ];
}
