<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMsgsAckMsgsAckMsg_ids;

/** Constructor model for msgs_ack of MsgsAck (crc32 62d6b459). */
final class TlMsgsAckMsgsAck extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_msgs_ack_msgs_ack';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function msgIds(): HasMany
    {
        return $this->tlChild(TlMsgsAckMsgsAckMsg_ids::class);
    }
}
