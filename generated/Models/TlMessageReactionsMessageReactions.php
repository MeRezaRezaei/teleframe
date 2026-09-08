<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReactionsMessageReactionsResults;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReactionsMessageReactionsRecent_reactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReactionsMessageReactionsTop_reactors;

/** Constructor model for messageReactions of MessageReactions (crc32 0a339f0b). */
final class TlMessageReactionsMessageReactions extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_reactions_message_reactions';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'min' => 'bool',
        'can_see_list' => 'bool',
        'reactions_as_tags' => 'bool',
    ];

    public function results(): HasMany
    {
        return $this->tlChild(TlMessageReactionsMessageReactionsResults::class);
    }
    public function recentReactions(): HasMany
    {
        return $this->tlChild(TlMessageReactionsMessageReactionsRecent_reactions::class);
    }
    public function topReactors(): HasMany
    {
        return $this->tlChild(TlMessageReactionsMessageReactionsTop_reactors::class);
    }
}
