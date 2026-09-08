<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageActionStarGift of MessageAction (crc32 ea2c31d3). */
final class TlMessageActionMessageActionStarGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'name_hidden' => 'bool',
        'saved' => 'bool',
        'converted' => 'bool',
        'upgraded' => 'bool',
        'refunded' => 'bool',
        'can_upgrade' => 'bool',
        'prepaid_upgrade' => 'bool',
        'upgrade_separate' => 'bool',
        'auction_acquired' => 'bool',
        'gift' => 'string',
        'message' => 'string',
        'convert_stars' => 'int',
        'upgrade_msg_id' => 'int',
        'upgrade_stars' => 'int',
        'from_id' => 'string',
        'peer' => 'string',
        'saved_id' => 'int',
        'prepaid_upgrade_hash' => 'string',
        'gift_msg_id' => 'int',
        'to_id' => 'string',
        'gift_num' => 'int',
    ];
}
