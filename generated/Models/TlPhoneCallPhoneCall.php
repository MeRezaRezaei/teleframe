<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallConnections;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallProtocol;

/** Constructor model for phoneCall of PhoneCall (crc32 30535af5). */
final class TlPhoneCallPhoneCall extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_call_phone_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'p2p_allowed' => 'bool',
        'video' => 'bool',
        'conference_supported' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'admin_id' => 'int',
        'participant_id' => 'int',
        'g_a_or_b' => 'string',
        'key_fingerprint' => 'int',
        'start_date' => 'int',
    ];

    public function connections(): HasMany
    {
        return $this->tlChild(TlPhoneCallPhoneCallConnections::class);
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(TlPhoneCallProtocol::class, 'protocol');
    }
    public function customParameters(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'custom_parameters');
    }
}
