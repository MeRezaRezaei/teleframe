<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesAlbumsAlbumsAlbums;

/** Constructor model for stories.albums of stories.Albums (crc32 c3987a3a). */
final class TlStoriesAlbumsAlbums extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stories_albums_albums';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function albums(): HasMany
    {
        return $this->tlChild(TlStoriesAlbumsAlbumsAlbums::class);
    }
}
