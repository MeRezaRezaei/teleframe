<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageDecryptedMessage;

/** Anchor model for TL type DecryptedMessageMedia (spec §4.1). */
final class TlDecryptedMessageMedia extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media';

    protected $guarded = [];

    public function media(): HasMany
    {
        return $this->hasMany(TlDecryptedMessageDecryptedMessage::class, 'media');
    }
}
