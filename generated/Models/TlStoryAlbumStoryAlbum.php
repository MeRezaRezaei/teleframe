<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for storyAlbum of StoryAlbum (crc32 9325705a). */
final class TlStoryAlbumStoryAlbum extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_story_album_story_album';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'album_id' => 'int',
        'title' => 'string',
    ];

    public function iconPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'icon_photo');
    }
    public function iconVideo(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'icon_video');
    }
}
