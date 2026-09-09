<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallProtocolPhoneCallProtocolLibrary_versions;

/** Constructor model for phoneCallProtocol of PhoneCallProtocol (crc32 fc878fc8). */
final class TlPhoneCallProtocolPhoneCallProtocol extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_call_protocol_phone_call_protocol';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'udp_p2p' => 'bool',
        'udp_reflector' => 'bool',
        'min_layer' => 'int',
        'max_layer' => 'int',
    ];

    public function libraryVersions(): HasMany
    {
        return $this->tlChild(TlPhoneCallProtocolPhoneCallProtocolLibrary_versions::class);
    }
}
