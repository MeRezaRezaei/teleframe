<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;

/** Constructor model for webPageAttributeStarGiftAuction of WebPageAttribute (crc32 01c641c2). */
final class TlWebPageAttributeWebPageAttributeStarGiftAuction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_web_page_attribute_web_page_attribute_star_gift_auction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'end_date' => 'int',
    ];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TlStarGift::class, 'gift');
    }
}
