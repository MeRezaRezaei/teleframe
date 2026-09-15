<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfPhotoVideoSize extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_photos_video_sizes';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'position' => 'int',
        'w' => 'int',
        'h' => 'int',
        'size' => 'int',
        'video_start_ts' => 'float',
        'emoji_id' => 'int',
        'sticker_id' => 'int',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TfPhoto::class, 'id', 'id');
    }
}
