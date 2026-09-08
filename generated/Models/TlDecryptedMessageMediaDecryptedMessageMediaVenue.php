<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for decryptedMessageMediaVenue of DecryptedMessageMedia (crc32 8a0df56f). */
final class TlDecryptedMessageMediaDecryptedMessageMediaVenue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_media_decrypted_message_media_venue';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'lat' => 'float',
        'tl_long' => 'float',
        'title' => 'string',
        'address' => 'string',
        'provider' => 'string',
        'venue_id' => 'string',
    ];
}
