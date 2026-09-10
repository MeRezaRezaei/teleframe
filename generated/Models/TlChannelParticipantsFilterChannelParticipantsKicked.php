<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for channelParticipantsKicked of ChannelParticipantsFilter (crc32 a3b54985). */
final class TlChannelParticipantsFilterChannelParticipantsKicked extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_participants_filter_channel_participants_kicked';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'q' => 'string',
    ];
}
