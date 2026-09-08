<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for payments.giveawayInfo of payments.GiveawayInfo (crc32 4367daa0). */
final class TlPaymentsGiveawayInfoGiveawayInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_giveaway_info_giveaway_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'participating' => 'bool',
        'preparing_results' => 'bool',
        'start_date' => 'int',
        'joined_too_early_date' => 'int',
        'admin_disallowed_chat_id' => 'int',
        'disallowed_country' => 'string',
    ];
}
