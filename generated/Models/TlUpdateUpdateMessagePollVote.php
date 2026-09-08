<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePollVoteOptions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessagePollVotePositions;

/** Constructor model for updateMessagePollVote of Update (crc32 7699f014). */
final class TlUpdateUpdateMessagePollVote extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_message_poll_vote';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'poll_id' => 'int',
        'peer' => 'string',
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
