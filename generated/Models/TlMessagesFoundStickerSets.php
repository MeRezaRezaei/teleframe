<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.FoundStickerSets (spec §4.1). */
final class TlMessagesFoundStickerSets extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_found_sticker_sets_found_sticker_sets';

    protected $guarded = [];
}
