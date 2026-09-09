<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmount;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionPeer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionStarsTransactionExtended_media;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for starsTransaction of StarsTransaction (crc32 13659eb0). */
final class TlStarsTransactionStarsTransaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_stars_transaction_stars_transaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'refund' => 'bool',
        'pending' => 'bool',
        'failed' => 'bool',
        'gift' => 'bool',
        'reaction' => 'bool',
        'stargift_upgrade' => 'bool',
        'business_transfer' => 'bool',
        'stargift_resale' => 'bool',
        'posts_search' => 'bool',
        'stargift_prepaid_upgrade' => 'bool',
        'stargift_drop_original_details' => 'bool',
        'phonegroup_message' => 'bool',
        'stargift_auction_bid' => 'bool',
        'offer' => 'bool',
        'tl_id' => 'string',
        'date' => 'int',
        'title' => 'string',
        'description' => 'string',
        'transaction_date' => 'int',
        'transaction_url' => 'string',
        'bot_payload' => 'string',
        'msg_id' => 'int',
        'subscription_period' => 'int',
        'giveaway_post_id' => 'int',
        'floodskip_number' => 'int',
        'starref_commission_permille' => 'int',
        'paid_messages' => 'int',
        'premium_gift_months' => 'int',
        'ads_proceeds_from_date' => 'int',
        'ads_proceeds_to_date' => 'int',
    ];

    public function extendedMedia(): HasMany
    {
        return $this->tlChild(TlStarsTransactionStarsTransactionExtended_media::class);
    }

    public function amount(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'amount');
    }
    public function peer(): BelongsTo
    {
        return $this->belongsTo(TlStarsTransactionPeer::class, 'peer');
    }
    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'photo');
    }
    public function stargift(): BelongsTo
    {
        return $this->belongsTo(TlStarGift::class, 'stargift');
    }
    public function starrefAmount(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'starref_amount');
    }
}
