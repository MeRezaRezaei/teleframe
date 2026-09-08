<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotosPhotosPhotosSlicePhotos;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotosPhotosPhotosSliceUsers;

/** Constructor model for photos.photosSlice of photos.Photos (crc32 15051f54). */
final class TlPhotosPhotosPhotosSlice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_photos_photos_photos_slice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function photos(): HasMany
    {
        return $this->tlChild(TlPhotosPhotosPhotosSlicePhotos::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPhotosPhotosPhotosSliceUsers::class);
    }
}
