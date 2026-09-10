<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for payments.checkCanSendGiftResultFail of payments.CheckCanSendGiftResult (crc32 d5e58274). */
final class TlPaymentsCheckCanSendGiftResultCheckCanSendGiftResultFail extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_check_can_send_gift_result_check__b2fde8ccbce4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function reason(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'reason');
    }
}
