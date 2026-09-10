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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for messageActionStarGift of MessageAction (crc32 ea2c31d3). */
final class TlMessageActionMessageActionStarGift extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_message_action_message_action_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'name_hidden' => 'bool',
        'saved' => 'bool',
        'converted' => 'bool',
        'upgraded' => 'bool',
        'refunded' => 'bool',
        'can_upgrade' => 'bool',
        'prepaid_upgrade' => 'bool',
        'upgrade_separate' => 'bool',
        'auction_acquired' => 'bool',
        'convert_stars' => 'int',
        'upgrade_msg_id' => 'int',
        'upgrade_stars' => 'int',
        'saved_id' => 'int',
        'prepaid_upgrade_hash' => 'string',
        'gift_msg_id' => 'int',
        'gift_num' => 'int',
    ];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TlStarGift::class, 'gift');
    }
    public function message(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'message');
    }
}
