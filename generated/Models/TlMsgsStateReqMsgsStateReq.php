<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMsgsStateReqMsgsStateReqMsg_ids;

/** Constructor model for msgs_state_req of MsgsStateReq (crc32 da69fb52). */
final class TlMsgsStateReqMsgsStateReq extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_msgs_state_req_msgs_state_req';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function msgIds(): HasMany
    {
        return $this->tlChild(TlMsgsStateReqMsgsStateReqMsg_ids::class);
    }
}
