<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for decryptedMessageMediaPhoto of DecryptedMessageMedia (crc32 f1fa8d78). */
final class TlDecryptedMessageMediaDecryptedMessageMediaPhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message_media_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'thumb' => 'string',
        'thumb_w' => 'int',
        'thumb_h' => 'int',
        'w' => 'int',
        'h' => 'int',
        'tl_size' => 'int',
        'tl_key' => 'string',
        'iv' => 'string',
        'caption' => 'string',
    ];
}
