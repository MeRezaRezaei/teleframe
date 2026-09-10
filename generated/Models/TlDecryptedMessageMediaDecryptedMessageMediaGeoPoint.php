<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for decryptedMessageMediaGeoPoint of DecryptedMessageMedia (crc32 35480a59). */
final class TlDecryptedMessageMediaDecryptedMessageMediaGeoPoint extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message__a644abd2ed29';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'lat' => 'float',
        'tl_long' => 'float',
    ];
}
