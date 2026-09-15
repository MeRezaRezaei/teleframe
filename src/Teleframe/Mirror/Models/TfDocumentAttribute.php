<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDocumentAttribute extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_documents_attributes';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'position' => 'int',
        'w' => 'int',
        'h' => 'int',
        'duration' => 'float',
        'video_start_ts' => 'float',
        'preload_prefix_size' => 'int',
        'mask' => 'bool',
        'round_message' => 'bool',
        'supports_streaming' => 'bool',
        'nosound' => 'bool',
        'voice' => 'bool',
        'free' => 'bool',
        'text_color' => 'bool',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(TfDocument::class, 'id', 'id');
    }
}
