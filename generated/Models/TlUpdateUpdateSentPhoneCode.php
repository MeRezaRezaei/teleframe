<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCode;

/** Constructor model for updateSentPhoneCode of Update (crc32 504aa18f). */
final class TlUpdateUpdateSentPhoneCode extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_sent_phone_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function sentCode(): BelongsTo
    {
        return $this->belongsTo(TlAuthSentCode::class, 'sent_code');
    }
}
