<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for decryptedMessageMediaAudio of DecryptedMessageMedia (crc32 57e0a9cb). */
final class TlDecryptedMessageMediaDecryptedMessageMediaAudio extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message_media_audio';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'duration' => 'int',
        'mime_type' => 'string',
        'tl_size' => 'int',
        'tl_key' => 'string',
        'iv' => 'string',
    ];
}
