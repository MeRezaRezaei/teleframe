<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePrivacy;

/** Anchor model for TL type PrivacyKey (spec §4.1). */
final class TlPrivacyKey extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_privacy_key_privacy_key_about';

    protected $guarded = [];

    public function key(): HasMany
    {
        return $this->hasMany(TlUpdateUpdatePrivacy::class, 'tl_key');
    }
}
