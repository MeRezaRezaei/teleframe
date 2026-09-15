<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStickerSetThumbVersion extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_sticker_sets_thumb_version';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'thumb_version' => 'int',
    ];

    public function stickerSet(): BelongsTo
    {
        return $this->belongsTo(TfStickerSet::class, 'id', 'id');
    }
}
