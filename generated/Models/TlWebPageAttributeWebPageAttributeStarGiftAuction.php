<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for webPageAttributeStarGiftAuction of WebPageAttribute (crc32 01c641c2). */
final class TlWebPageAttributeWebPageAttributeStarGiftAuction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_page_attribute_web_page_attribute_star_gift_auction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'gift' => 'string',
        'end_date' => 'int',
    ];
}
