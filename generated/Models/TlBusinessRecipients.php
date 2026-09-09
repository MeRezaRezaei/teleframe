<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessAwayMessageBusinessAwayMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessGreetingMessageBusinessGreetingMessage;

/** Anchor model for TL type BusinessRecipients (spec §4.1). */
final class TlBusinessRecipients extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_business_recipients';

    protected $guarded = [];

    public function recipients(): HasMany
    {
        return $this->hasMany(TlBusinessGreetingMessageBusinessGreetingMessage::class, 'recipients');
    }
    public function recipientsBusinessAwayMessage(): HasMany
    {
        return $this->hasMany(TlBusinessAwayMessageBusinessAwayMessage::class, 'recipients');
    }
}
