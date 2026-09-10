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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPageAttributes;

/** Constructor model for webPage of WebPage (crc32 e89c45b2). */
final class TlWebPageWebPage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_web_page_web_page';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_large_media' => 'bool',
        'video_cover_photo' => 'bool',
        'tl_id' => 'int',
        'url' => 'string',
        'display_url' => 'string',
        'hash' => 'int',
        'tl_type' => 'string',
        'site_name' => 'string',
        'title' => 'string',
        'description' => 'string',
        'embed_url' => 'string',
        'embed_type' => 'string',
        'embed_width' => 'int',
        'embed_height' => 'int',
        'duration' => 'int',
        'author' => 'string',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlWebPageWebPageAttributes::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'photo');
    }
    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
    public function cachedPage(): BelongsTo
    {
        return $this->belongsTo(TlPage::class, 'cached_page');
    }
}
