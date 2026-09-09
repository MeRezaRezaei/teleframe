<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for payments.giveawayInfoResults of payments.GiveawayInfo (crc32 e175e66f). */
final class TlPaymentsGiveawayInfoGiveawayInfoResults extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_giveaway_info_giveaway_info_results';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'winner' => 'bool',
        'refunded' => 'bool',
        'start_date' => 'int',
        'gift_code_slug' => 'string',
        'stars_prize' => 'int',
        'finish_date' => 'int',
        'winners_count' => 'int',
        'activated_count' => 'int',
    ];
}
