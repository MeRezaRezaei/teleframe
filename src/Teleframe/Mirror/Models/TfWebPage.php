<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfWebPage extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_web_pages';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'date' => 'int',
    ];

    public function url(): HasOne
    {
        return $this->hasOne(TfWebPageUrl::class, 'id', 'id');
    }
}
