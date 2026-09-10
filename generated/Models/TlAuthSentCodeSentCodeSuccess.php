<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthAuthorization;

/** Constructor model for auth.sentCodeSuccess of auth.SentCode (crc32 2390fe44). */
final class TlAuthSentCodeSentCodeSuccess extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_sent_code_sent_code_success';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(TlAuthAuthorization::class, 'tl_authorization');
    }
}
