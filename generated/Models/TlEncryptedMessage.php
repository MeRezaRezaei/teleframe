<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewEncryptedMessage;

/** Anchor model for TL type EncryptedMessage (spec §4.1). */
final class TlEncryptedMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_encrypted_message_encrypted_message';

    protected $guarded = [];

    public function message(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNewEncryptedMessage::class, 'message');
    }
}
