<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaExternalDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageExtendedMediaMessageExtendedMediaPreview;

/** Anchor model for TL type PhotoSize (spec §4.1). */
final class TlPhotoSize extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_photo_size_photo_cached_size';

    protected $guarded = [];

    public function thumb(): HasMany
    {
        return $this->hasMany(TlDecryptedMessageMediaDecryptedMessageMediaExternalDocument::class, 'thumb');
    }
    public function thumbMessageExtendedMediaPreview(): HasMany
    {
        return $this->hasMany(TlMessageExtendedMediaMessageExtendedMediaPreview::class, 'thumb');
    }
}
