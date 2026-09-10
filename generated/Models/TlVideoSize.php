<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChatPhotoInputChatUploadedPhoto;

/** Anchor model for TL type VideoSize (spec §4.1). */
final class TlVideoSize extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_video_size_video_size';

    protected $guarded = [];

    public function videoEmojiMarkup(): HasMany
    {
        return $this->hasMany(TlInputChatPhotoInputChatUploadedPhoto::class, 'video_emoji_markup');
    }
}
