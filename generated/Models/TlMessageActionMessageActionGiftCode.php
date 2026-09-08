<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageActionGiftCode of MessageAction (crc32 31c48347). */
final class TlMessageActionMessageActionGiftCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_gift_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'via_giveaway' => 'bool',
        'unclaimed' => 'bool',
        'boost_peer' => 'string',
        'days' => 'int',
        'slug' => 'string',
        'currency' => 'string',
        'amount' => 'int',
        'crypto_currency' => 'string',
        'crypto_amount' => 'int',
        'message' => 'string',
    ];
}
