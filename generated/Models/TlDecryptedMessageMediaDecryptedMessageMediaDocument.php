<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessage1652f9c81874Attributes;

/** Constructor model for decryptedMessageMediaDocument of DecryptedMessageMedia (crc32 6abd9782). */
final class TlDecryptedMessageMediaDecryptedMessageMediaDocument extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message__1652f9c81874';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'thumb' => 'string',
        'thumb_w' => 'int',
        'thumb_h' => 'int',
        'mime_type' => 'string',
        'tl_size' => 'int',
        'tl_key' => 'string',
        'iv' => 'string',
        'caption' => 'string',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlDecryptedMessageMediaDecryptedMessage1652f9c81874Attributes::class);
    }
}
