<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for savedReactionTag of SavedReactionTag (crc32 cb6ff828). */
final class TlSavedReactionTagSavedReactionTag extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_saved_reaction_tag_saved_reaction_tag';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'reaction' => 'string',
        'title' => 'string',
        'count' => 'int',
    ];
}
