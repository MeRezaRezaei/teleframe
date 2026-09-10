<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPage;

/** Anchor model for TL type Page (spec §4.1). */
final class TlPage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_page_page';

    protected $guarded = [];

    public function cachedPage(): HasMany
    {
        return $this->hasMany(TlWebPageWebPage::class, 'cached_page');
    }
}
