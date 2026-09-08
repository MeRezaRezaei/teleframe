<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for payments.checkCanSendGiftResultOk of payments.CheckCanSendGiftResult (crc32 374fa7ad). */
final class TlPaymentsCheckCanSendGiftResultCheckCanSendGiftResultOk extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_check_can_send_gift_result_check__7028254cf06b';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
