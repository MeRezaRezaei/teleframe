<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentRequestedInfo;

/** Constructor model for updateBotPrecheckoutQuery of Update (crc32 8caa9a96). */
final class TlUpdateUpdateBotPrecheckoutQuery extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_bot_precheckout_query';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'query_id' => 'int',
        'user_id' => 'int',
        'payload' => 'string',
        'shipping_option_id' => 'string',
        'currency' => 'string',
        'total_amount' => 'int',
    ];

    public function info(): BelongsTo
    {
        return $this->belongsTo(TlPaymentRequestedInfo::class, 'info');
    }
}
