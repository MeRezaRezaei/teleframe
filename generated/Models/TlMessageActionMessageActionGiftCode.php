<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for messageActionGiftCode of MessageAction (crc32 31c48347). */
final class TlMessageActionMessageActionGiftCode extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_message_action_message_action_gift_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'via_giveaway' => 'bool',
        'unclaimed' => 'bool',
        'days' => 'int',
        'slug' => 'string',
        'currency' => 'string',
        'amount' => 'int',
        'crypto_currency' => 'string',
        'crypto_amount' => 'int',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'message');
    }
}
