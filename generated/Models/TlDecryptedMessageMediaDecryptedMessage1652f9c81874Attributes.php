<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param attributes (table tl_decrypted_message_media_decrypted_message__fa113370e99a). */
final class TlDecryptedMessageMediaDecryptedMessage1652f9c81874Attributes extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message__fa113370e99a';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
