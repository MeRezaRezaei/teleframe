<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaInvoice;

/** Anchor model for TL type MessageExtendedMedia (spec §4.1). */
final class TlMessageExtendedMedia extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_extended_media_message_extended_media';

    protected $guarded = [];

    public function extendedMedia(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaInvoice::class, 'extended_media');
    }
}
