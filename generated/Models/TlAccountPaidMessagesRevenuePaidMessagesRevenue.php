<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for account.paidMessagesRevenue of account.PaidMessagesRevenue (crc32 1e109708). */
final class TlAccountPaidMessagesRevenuePaidMessagesRevenue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_paid_messages_revenue_paid_messages_revenue';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'stars_amount' => 'int',
    ];
}
