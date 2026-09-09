<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoice;

/** Constructor model for inputMediaInvoice of InputMedia (crc32 405fef0d). */
final class TlInputMediaInputMediaInvoice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_invoice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'title' => 'string',
        'description' => 'string',
        'payload' => 'string',
        'provider' => 'string',
        'start_param' => 'string',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlInputWebDocument::class, 'photo');
    }
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TlInvoice::class, 'invoice');
    }
    public function providerData(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'provider_data');
    }
    public function extendedMedia(): BelongsTo
    {
        return $this->belongsTo(TlInputMedia::class, 'extended_media');
    }
}
