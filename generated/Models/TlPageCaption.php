<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockInputPageBlockMap;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockAudio;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockCollage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockEmbed;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockEmbedPost;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockMap;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockSlideshow;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockVideo;

/** Anchor model for TL type PageCaption (spec §4.1). */
final class TlPageCaption extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_page_caption';

    protected $guarded = [];

    public function caption(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockPhoto::class, 'caption');
    }
    public function captionInputPageBlockMap(): HasMany
    {
        return $this->hasMany(TlPageBlockInputPageBlockMap::class, 'caption');
    }
    public function captionPageBlockAudio(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockAudio::class, 'caption');
    }
    public function captionPageBlockCollage(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockCollage::class, 'caption');
    }
    public function captionPageBlockEmbed(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockEmbed::class, 'caption');
    }
    public function captionPageBlockEmbedPost(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockEmbedPost::class, 'caption');
    }
    public function captionPageBlockMap(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockMap::class, 'caption');
    }
    public function captionPageBlockSlideshow(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockSlideshow::class, 'caption');
    }
    public function captionPageBlockVideo(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockVideo::class, 'caption');
    }
}
