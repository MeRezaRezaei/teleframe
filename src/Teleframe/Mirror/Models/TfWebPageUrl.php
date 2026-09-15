<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfWebPageUrl extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_web_pages_url';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
    ];

    public function webPage(): BelongsTo
    {
        return $this->belongsTo(TfWebPage::class, 'id', 'id');
    }
}
