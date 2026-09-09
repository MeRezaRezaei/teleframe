<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChatPhotoInputChatPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaDocumentExternal;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputRichFileInputRichFilePhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickeredMediaInputStickeredMediaPhoto;

/** Anchor model for TL type InputPhoto (spec §4.1). */
final class TlInputPhoto extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_photo';

    protected $guarded = [];

    public function id(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaPhoto::class, 'tl_id');
    }
    public function idInputChatPhoto(): HasMany
    {
        return $this->hasMany(TlInputChatPhotoInputChatPhoto::class, 'tl_id');
    }
    public function idInputStickeredMediaPhoto(): HasMany
    {
        return $this->hasMany(TlInputStickeredMediaInputStickeredMediaPhoto::class, 'tl_id');
    }
    public function photo(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResultPhoto::class, 'photo');
    }
    public function photoInputRichFilePhoto(): HasMany
    {
        return $this->hasMany(TlInputRichFileInputRichFilePhoto::class, 'photo');
    }
    public function videoCover(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaUploadedDocument::class, 'video_cover');
    }
    public function videoCoverInputMediaDocument(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaDocument::class, 'video_cover');
    }
    public function videoCoverInputMediaDocumentExternal(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaDocumentExternal::class, 'video_cover');
    }
}
