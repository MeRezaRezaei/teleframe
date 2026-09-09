<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messages.savedReactionTagsNotModified of messages.SavedReactionTags (crc32 889b59ef). */
final class TlMessagesSavedReactionTagsSavedReactionTagsNotModified extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_saved_reaction_tags_saved_reactio_4b74c8258774';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
