<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;

/** Anchor model for TL type FactCheck (spec §4.1). */
final class TlFactCheck extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_fact_check_fact_check';

    protected $guarded = [];

    public function factcheck(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'factcheck');
    }
}
