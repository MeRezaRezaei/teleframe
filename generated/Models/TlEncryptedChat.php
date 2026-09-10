<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEncryption;

/** Anchor model for TL type EncryptedChat (spec §4.1). */
final class TlEncryptedChat extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_encrypted_chat_encrypted_chat';

    protected $guarded = [];

    public function chat(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateEncryption::class, 'chat');
    }
}
