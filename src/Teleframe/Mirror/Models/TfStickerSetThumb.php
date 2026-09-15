<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStickerSetThumb extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_sticker_sets_thumbs';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'position' => 'int',
        'w' => 'int',
        'h' => 'int',
        'size' => 'int',
    ];

    public function stickerSet(): BelongsTo
    {
        return $this->belongsTo(TfStickerSet::class, 'id', 'id');
    }
}
