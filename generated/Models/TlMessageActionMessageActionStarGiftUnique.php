<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmount;

/** Constructor model for messageActionStarGiftUnique of MessageAction (crc32 e6c31522). */
final class TlMessageActionMessageActionStarGiftUnique extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_message_action_message_action_star_gift_unique';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'upgrade' => 'bool',
        'transferred' => 'bool',
        'saved' => 'bool',
        'refunded' => 'bool',
        'prepaid_upgrade' => 'bool',
        'assigned' => 'bool',
        'from_offer' => 'bool',
        'craft' => 'bool',
        'can_export_at' => 'int',
        'transfer_stars' => 'int',
        'saved_id' => 'int',
        'can_transfer_at' => 'int',
        'can_resell_at' => 'int',
        'drop_original_details_stars' => 'int',
        'can_craft_at' => 'int',
    ];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TlStarGift::class, 'gift');
    }
    public function resaleAmount(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'resale_amount');
    }
}
