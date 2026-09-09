<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCall;

/** Constructor model for updatePhoneCall of Update (crc32 ab0f6b1e). */
final class TlUpdateUpdatePhoneCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_phone_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function phoneCall(): BelongsTo
    {
        return $this->belongsTo(TlPhoneCall::class, 'phone_call');
    }
}
