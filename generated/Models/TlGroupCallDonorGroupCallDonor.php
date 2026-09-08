<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for groupCallDonor of GroupCallDonor (crc32 ee430c85). */
final class TlGroupCallDonorGroupCallDonor extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_group_call_donor_group_call_donor';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'top' => 'bool',
        'my' => 'bool',
        'peer_id' => 'string',
        'stars' => 'int',
    ];
}
