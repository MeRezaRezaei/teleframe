<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResults;

/** Constructor model for updateMessagePoll of Update (crc32 d64c522b). */
final class TlUpdateUpdateMessagePoll extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_message_poll';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'msg_id' => 'int',
        'top_msg_id' => 'int',
        'poll_id' => 'int',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(TlPoll::class, 'poll');
    }
    public function results(): BelongsTo
    {
        return $this->belongsTo(TlPollResults::class, 'results');
    }
}
