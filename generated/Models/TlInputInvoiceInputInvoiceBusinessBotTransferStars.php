<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUser;

/** Constructor model for inputInvoiceBusinessBotTransferStars of InputInvoice (crc32 f4997e42). */
final class TlInputInvoiceInputInvoiceBusinessBotTransferStars extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_invoice_input_invoice_business_bot_transfer_stars';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'stars' => 'int',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TlInputUser::class, 'bot');
    }
}
