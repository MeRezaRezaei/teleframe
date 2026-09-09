<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewQuickReply;

/** Anchor model for TL type QuickReply (spec §4.1). */
final class TlQuickReply extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_quick_reply';

    protected $guarded = [];

    public function quickReply(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNewQuickReply::class, 'quick_reply');
    }
}
