<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGiveawayResultsWinners;

/** Constructor model for messageMediaGiveawayResults of MessageMedia (crc32 ceaa3ea1). */
final class TlMessageMediaMessageMediaGiveawayResults extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_giveaway_results';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'only_new_subscribers' => 'bool',
        'refunded' => 'bool',
        'channel_id' => 'int',
        'additional_peers_count' => 'int',
        'launch_msg_id' => 'int',
        'winners_count' => 'int',
        'unclaimed_count' => 'int',
        'months' => 'int',
        'stars' => 'int',
        'prize_description' => 'string',
        'until_date' => 'int',
    ];

    public function winners(): HasMany
    {
        return $this->tlChild(TlMessageMediaMessageMediaGiveawayResultsWinners::class);
    }
}
