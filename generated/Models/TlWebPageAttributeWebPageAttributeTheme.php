<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlThemeSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeThemeDocuments;

/** Constructor model for webPageAttributeTheme of WebPageAttribute (crc32 54b56617). */
final class TlWebPageAttributeWebPageAttributeTheme extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_web_page_attribute_web_page_attribute_theme';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function documents(): HasMany
    {
        return $this->tlChild(TlWebPageAttributeWebPageAttributeThemeDocuments::class);
    }

    public function settings(): BelongsTo
    {
        return $this->belongsTo(TlThemeSettings::class, 'settings');
    }
}
