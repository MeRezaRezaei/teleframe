<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for messageActionGiftPremium of MessageAction (crc32 48e91302). */
final class TlMessageActionMessageActionGiftPremium extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_gift_premium';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'currency' => 'string',
        'amount' => 'int',
        'days' => 'int',
        'crypto_currency' => 'string',
        'crypto_amount' => 'int',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'message');
    }
}
