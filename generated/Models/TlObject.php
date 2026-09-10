<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRpcResultRpcResult;

/** Anchor model for TL type Object (spec §4.1). */
final class TlObject extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_object_gzip_packed';

    protected $guarded = [];

    public function result(): HasMany
    {
        return $this->hasMany(TlRpcResultRpcResult::class, 'result');
    }
}
