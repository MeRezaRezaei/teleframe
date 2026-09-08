<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for webPageAttributeUniqueStarGift of WebPageAttribute (crc32 cf6f6db8). */
final class TlWebPageAttributeWebPageAttributeUniqueStarGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_page_attribute_web_page_attribute_unique_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'gift' => 'string',
    ];
}
