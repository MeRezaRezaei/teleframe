<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageActionStarGiftUnique of MessageAction (crc32 e6c31522). */
final class TlMessageActionMessageActionStarGiftUnique extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_star_gift_unique';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'upgrade' => 'bool',
        'transferred' => 'bool',
        'saved' => 'bool',
        'refunded' => 'bool',
        'prepaid_upgrade' => 'bool',
        'assigned' => 'bool',
        'from_offer' => 'bool',
        'craft' => 'bool',
        'gift' => 'string',
        'can_export_at' => 'int',
        'transfer_stars' => 'int',
        'from_id' => 'string',
        'peer' => 'string',
        'saved_id' => 'int',
        'resale_amount' => 'string',
        'can_transfer_at' => 'int',
        'can_resell_at' => 'int',
        'drop_original_details_stars' => 'int',
        'can_craft_at' => 'int',
    ];
}
