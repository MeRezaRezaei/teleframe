<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatReactionsChatReactionsSomeReactions;

/** Constructor model for chatReactionsSome of ChatReactions (crc32 661d4037). */
final class TlChatReactionsChatReactionsSome extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chat_reactions_chat_reactions_some';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function reactions(): HasMany
    {
        return $this->tlChild(TlChatReactionsChatReactionsSomeReactions::class);
    }
}
