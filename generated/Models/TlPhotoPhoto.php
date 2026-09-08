<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotoPhotoSizes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotoPhotoVideo_sizes;

/** Constructor model for photo of Photo (crc32 fb197a65). */
final class TlPhotoPhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_photo_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_stickers' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'file_reference' => 'string',
        'date' => 'int',
        'dc_id' => 'int',
    ];

    public function sizes(): HasMany
    {
        return $this->tlChild(TlPhotoPhotoSizes::class);
    }
    public function videoSizes(): HasMany
    {
        return $this->tlChild(TlPhotoPhotoVideo_sizes::class);
    }
}
