<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageLayerDecryptedMessageLayer;

/** Anchor model for TL type DecryptedMessage (spec §4.1). */
final class TlDecryptedMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_decrypted_message';

    protected $guarded = [];

    public function message(): HasMany
    {
        return $this->hasMany(TlDecryptedMessageLayerDecryptedMessageLayer::class, 'message');
    }
}
