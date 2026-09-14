<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDocumentVideoThumb extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_documents_video_thumbs';

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

    public function document(): BelongsTo
    {
        return $this->belongsTo(TfDocument::class, 'id', 'id');
    }
}
