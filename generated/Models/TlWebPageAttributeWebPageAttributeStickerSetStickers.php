<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param stickers (table tl_web_page_attribute_web_page_attribute_stic_5d4fa9f0c49f). */
final class TlWebPageAttributeWebPageAttributeStickerSetStickers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_web_page_attribute_web_page_attribute_stic_5d4fa9f0c49f';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
