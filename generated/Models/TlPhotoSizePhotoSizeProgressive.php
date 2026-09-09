<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotoSizePhotoSizeProgressiveSizes;

/** Constructor model for photoSizeProgressive of PhotoSize (crc32 fa3efb95). */
final class TlPhotoSizePhotoSizeProgressive extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_photo_size_photo_size_progressive';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_type' => 'string',
        'w' => 'int',
        'h' => 'int',
    ];

    public function sizes(): HasMany
    {
        return $this->tlChild(TlPhotoSizePhotoSizeProgressiveSizes::class);
    }
}
