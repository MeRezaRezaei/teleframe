<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param documents (table tl_web_page_attribute_web_page_attribute_theme__documents). */
final class TlWebPageAttributeWebPageAttributeThemeDocuments extends TlAnchorModel
{
    protected $table = 'tl_web_page_attribute_web_page_attribute_theme__documents';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
