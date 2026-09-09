<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChatPhotoInputChatUploadedPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedPhoto;

/** Anchor model for TL type InputFile (spec §4.1). */
final class TlInputFile extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_file';

    protected $guarded = [];

    public function file(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaUploadedPhoto::class, 'file');
    }
    public function fileInputChatUploadedPhoto(): HasMany
    {
        return $this->hasMany(TlInputChatPhotoInputChatUploadedPhoto::class, 'file');
    }
    public function fileInputMediaUploadedDocument(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaUploadedDocument::class, 'file');
    }
    public function thumb(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaUploadedDocument::class, 'thumb');
    }
    public function video(): HasMany
    {
        return $this->hasMany(TlInputChatPhotoInputChatUploadedPhoto::class, 'video');
    }
}
