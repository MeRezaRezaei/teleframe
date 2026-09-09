<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeStickerSetStickers;

/** Constructor model for webPageAttributeStickerSet of WebPageAttribute (crc32 50cc03d3). */
final class TlWebPageAttributeWebPageAttributeStickerSet extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_web_page_attribute_web_page_attribute_sticker_set';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'emojis' => 'bool',
        'text_color' => 'bool',
    ];

    public function stickers(): HasMany
    {
        return $this->tlChild(TlWebPageAttributeWebPageAttributeStickerSetStickers::class);
    }
}
