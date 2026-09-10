<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessAwayMessageInputBusinessAwayMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessGreetingMessageInputBusinessGreetingMessage;

/** Anchor model for TL type InputBusinessRecipients (spec §4.1). */
final class TlInputBusinessRecipients extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_business_recipients_input_business_recipients';

    protected $guarded = [];

    public function recipients(): HasMany
    {
        return $this->hasMany(TlInputBusinessGreetingMessageInputBusinessGreetingMessage::class, 'recipients');
    }
    public function recipientsInputBusinessAwayMessage(): HasMany
    {
        return $this->hasMany(TlInputBusinessAwayMessageInputBusinessAwayMessage::class, 'recipients');
    }
}
