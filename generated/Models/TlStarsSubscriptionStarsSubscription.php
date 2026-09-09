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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsSubscriptionPricing;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for starsSubscription of StarsSubscription (crc32 2e6eab1a). */
final class TlStarsSubscriptionStarsSubscription extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_stars_subscription_stars_subscription';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'canceled' => 'bool',
        'can_refulfill' => 'bool',
        'missing_balance' => 'bool',
        'bot_canceled' => 'bool',
        'tl_id' => 'string',
        'until_date' => 'int',
        'chat_invite_hash' => 'string',
        'title' => 'string',
        'invoice_slug' => 'string',
    ];

    public function pricing(): BelongsTo
    {
        return $this->belongsTo(TlStarsSubscriptionPricing::class, 'pricing');
    }
    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'photo');
    }
}
