<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfDocument extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_documents';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'size' => 'int',
        'dc_id' => 'int',
    ];

    public function attributes(): HasMany
    {
        return $this->hasMany(TfDocumentAttribute::class, 'id', 'id');
    }

    public function thumbs(): HasMany
    {
        return $this->hasMany(TfDocumentThumb::class, 'id', 'id');
    }

    public function videoThumbs(): HasMany
    {
        return $this->hasMany(TfDocumentVideoThumb::class, 'id', 'id');
    }
}
