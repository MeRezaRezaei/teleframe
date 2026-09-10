<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for decryptedMessageMediaVideo of DecryptedMessageMedia (crc32 970c8c0e). */
final class TlDecryptedMessageMediaDecryptedMessageMediaVideo extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message_media_video';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'thumb' => 'string',
        'thumb_w' => 'int',
        'thumb_h' => 'int',
        'duration' => 'int',
        'mime_type' => 'string',
        'w' => 'int',
        'h' => 'int',
        'tl_size' => 'int',
        'tl_key' => 'string',
        'iv' => 'string',
        'caption' => 'string',
    ];
}
