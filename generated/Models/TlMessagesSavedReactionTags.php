<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.SavedReactionTags (spec §4.1). */
final class TlMessagesSavedReactionTags extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_saved_reaction_tags_saved_reaction_tags';

    protected $guarded = [];
}
