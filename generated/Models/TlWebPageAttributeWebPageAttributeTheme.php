<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeThemeDocuments;

/** Constructor model for webPageAttributeTheme of WebPageAttribute (crc32 54b56617). */
final class TlWebPageAttributeWebPageAttributeTheme extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_page_attribute_web_page_attribute_theme';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'settings' => 'string',
    ];

    public function documents(): HasMany
    {
        return $this->tlChild(TlWebPageAttributeWebPageAttributeThemeDocuments::class);
    }
}
