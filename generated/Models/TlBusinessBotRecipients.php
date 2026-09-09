<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConnectedBotConnectedBot;

/** Anchor model for TL type BusinessBotRecipients (spec §4.1). */
final class TlBusinessBotRecipients extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_business_bot_recipients';

    protected $guarded = [];

    public function recipients(): HasMany
    {
        return $this->hasMany(TlConnectedBotConnectedBot::class, 'recipients');
    }
}
