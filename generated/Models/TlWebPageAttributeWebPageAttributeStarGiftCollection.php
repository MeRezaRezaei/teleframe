<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeStar0f76dd30baafIcons;

/** Constructor model for webPageAttributeStarGiftCollection of WebPageAttribute (crc32 31cad303). */
final class TlWebPageAttributeWebPageAttributeStarGiftCollection extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_page_attribute_web_page_attribute_star_0f76dd30baaf';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function icons(): HasMany
    {
        return $this->tlChild(TlWebPageAttributeWebPageAttributeStar0f76dd30baafIcons::class);
    }
}
