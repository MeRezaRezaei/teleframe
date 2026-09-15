<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfPhoto extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_photos';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'dc_id' => 'int',
        'has_stickers' => 'bool',
    ];

    public function sizes(): HasMany
    {
        return $this->hasMany(TfPhotoSize::class, 'id', 'id');
    }

    public function videoSizes(): HasMany
    {
        return $this->hasMany(TfPhotoVideoSize::class, 'id', 'id');
    }
}
