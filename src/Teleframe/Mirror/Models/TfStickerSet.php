<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfStickerSet extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_sticker_sets';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'access_hash' => 'int',
        'count' => 'int',
        'hash' => 'int',
        'archived' => 'bool',
        'official' => 'bool',
        'masks' => 'bool',
        'emojis' => 'bool',
        'text_color' => 'bool',
        'channel_emoji_status' => 'bool',
        'creator' => 'bool',
    ];

    public function thumbs(): HasMany
    {
        return $this->hasMany(TfStickerSetThumb::class, 'id', 'id');
    }

    public function installedDate(): HasOne
    {
        return $this->hasOne(TfStickerSetInstalledDate::class, 'id', 'id');
    }

    public function thumbDcId(): HasOne
    {
        return $this->hasOne(TfStickerSetThumbDcId::class, 'id', 'id');
    }

    public function thumbVersion(): HasOne
    {
        return $this->hasOne(TfStickerSetThumbVersion::class, 'id', 'id');
    }

    public function thumbDocumentId(): HasOne
    {
        return $this->hasOne(TfStickerSetThumbDocumentId::class, 'id', 'id');
    }
}
