<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageDecryptedMessageService;

/** Anchor model for TL type DecryptedMessageAction (spec §4.1). */
final class TlDecryptedMessageAction extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_decrypted_message_action';

    protected $guarded = [];

    public function action(): HasMany
    {
        return $this->hasMany(TlDecryptedMessageDecryptedMessageService::class, 'action');
    }
}
