<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePollVoteOptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePollVotePositions;

/** Constructor model for updateMessagePollVote of Update (crc32 7699f014). */
final class TlUpdateUpdateMessagePollVote extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_message_poll_vote';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'poll_id' => 'int',
        'qts' => 'int',
    ];

    public function options(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateMessagePollVoteOptions::class);
    }
    public function positions(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateMessagePollVotePositions::class);
    }
}
