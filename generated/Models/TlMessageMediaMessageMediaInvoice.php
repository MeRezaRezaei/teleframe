<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageExtendedMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for messageMediaInvoice of MessageMedia (crc32 f6a548d3). */
final class TlMessageMediaMessageMediaInvoice extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_invoice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'shipping_address_requested' => 'bool',
        'test' => 'bool',
        'title' => 'string',
        'description' => 'string',
        'receipt_msg_id' => 'int',
        'currency' => 'string',
        'total_amount' => 'int',
        'start_param' => 'string',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'photo');
    }
    public function extendedMedia(): BelongsTo
    {
        return $this->belongsTo(TlMessageExtendedMedia::class, 'extended_media');
    }
}
