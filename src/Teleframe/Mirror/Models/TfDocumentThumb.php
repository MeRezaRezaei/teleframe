<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDocumentThumb extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_documents_thumbs';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'position' => 'int',
        'w' => 'int',
        'h' => 'int',
        'size' => 'int',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(TfDocument::class, 'id', 'id');
    }
}
