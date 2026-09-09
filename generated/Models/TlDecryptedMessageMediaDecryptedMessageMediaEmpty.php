<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for decryptedMessageMediaEmpty of DecryptedMessageMedia (crc32 089f5c4a). */
final class TlDecryptedMessageMediaDecryptedMessageMediaEmpty extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message_media_empty';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
