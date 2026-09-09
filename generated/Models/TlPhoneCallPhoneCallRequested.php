<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallProtocol;

/** Constructor model for phoneCallRequested of PhoneCall (crc32 14b0ed0c). */
final class TlPhoneCallPhoneCallRequested extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_call_phone_call_requested';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'video' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'admin_id' => 'int',
        'participant_id' => 'int',
        'g_a_hash' => 'string',
    ];

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(TlPhoneCallProtocol::class, 'protocol');
    }
}
