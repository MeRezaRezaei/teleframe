<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageMediaInvoice of MessageMedia (crc32 f6a548d3). */
final class TlMessageMediaMessageMediaInvoice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_media_message_media_invoice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'shipping_address_requested' => 'bool',
        'test' => 'bool',
        'title' => 'string',
        'description' => 'string',
        'photo' => 'string',
        'receipt_msg_id' => 'int',
        'currency' => 'string',
        'total_amount' => 'int',
        'start_param' => 'string',
        'extended_media' => 'string',
    ];
}
