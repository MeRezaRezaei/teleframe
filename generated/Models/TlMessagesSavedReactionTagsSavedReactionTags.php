<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSavedReactionTagsSavedReactionTagsTags;

/** Constructor model for messages.savedReactionTags of messages.SavedReactionTags (crc32 3259950a). */
final class TlMessagesSavedReactionTagsSavedReactionTags extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_saved_reaction_tags_saved_reaction_tags';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function tags(): HasMany
    {
        return $this->tlChild(TlMessagesSavedReactionTagsSavedReactionTagsTags::class);
    }
}
