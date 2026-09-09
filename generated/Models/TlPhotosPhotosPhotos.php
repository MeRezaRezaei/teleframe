<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotosPhotosPhotosPhotos;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotosPhotosPhotosUsers;

/** Constructor model for photos.photos of photos.Photos (crc32 8dca6aa5). */
final class TlPhotosPhotosPhotos extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_photos_photos_photos';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function photos(): HasMany
    {
        return $this->tlChild(TlPhotosPhotosPhotosPhotos::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPhotosPhotosPhotosUsers::class);
    }
}
