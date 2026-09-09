<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchCounterSearchCounter;

/** Anchor model for TL type MessagesFilter (spec §4.1). */
final class TlMessagesFilter extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_filter';

    protected $guarded = [];

    public function filter(): HasMany
    {
        return $this->hasMany(TlMessagesSearchCounterSearchCounter::class, 'filter');
    }
}
