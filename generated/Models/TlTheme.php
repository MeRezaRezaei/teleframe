<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateTheme;

/** Anchor model for TL type Theme (spec §4.1). */
final class TlTheme extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_theme';

    protected $guarded = [];

    public function theme(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateTheme::class, 'theme');
    }
}
