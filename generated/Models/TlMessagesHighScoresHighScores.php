<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesHighScoresHighScoresScores;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesHighScoresHighScoresUsers;

/** Constructor model for messages.highScores of messages.HighScores (crc32 9a3bfd99). */
final class TlMessagesHighScoresHighScores extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_high_scores_high_scores';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function scores(): HasMany
    {
        return $this->tlChild(TlMessagesHighScoresHighScoresScores::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesHighScoresHighScoresUsers::class);
    }
}
