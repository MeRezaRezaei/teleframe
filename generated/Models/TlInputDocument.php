<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBusinessIntroInputBusinessIntro;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFileInputFileStoryDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichFileInputRichFileDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickerSetItemInputStickerSetItem;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickeredMediaInputStickeredMediaDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebFileLocationInputWebFileAudioAlbumThumbLocation;

/** Anchor model for TL type InputDocument (spec §4.1). */
final class TlInputDocument extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_document_input_document';

    protected $guarded = [];

    public function document(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResultDocument::class, 'document');
    }
    public function documentInputRichFileDocument(): HasMany
    {
        return $this->hasMany(TlInputRichFileInputRichFileDocument::class, 'document');
    }
    public function documentInputStickerSetItem(): HasMany
    {
        return $this->hasMany(TlInputStickerSetItemInputStickerSetItem::class, 'document');
    }
    public function documentInputWebFileAudioAlbumThumbLocation(): HasMany
    {
        return $this->hasMany(TlInputWebFileLocationInputWebFileAudioAlbumThumbLocation::class, 'document');
    }
    public function id(): HasMany
    {
        return $this->hasMany(TlInputFileInputFileStoryDocument::class, 'tl_id');
    }
    public function idInputMediaDocument(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaDocument::class, 'tl_id');
    }
    public function idInputStickeredMediaDocument(): HasMany
    {
        return $this->hasMany(TlInputStickeredMediaInputStickeredMediaDocument::class, 'tl_id');
    }
    public function sticker(): HasMany
    {
        return $this->hasMany(TlInputBusinessIntroInputBusinessIntro::class, 'sticker');
    }
    public function video(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaUploadedPhoto::class, 'video');
    }
    public function videoInputMediaPhoto(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaPhoto::class, 'video');
    }
}
