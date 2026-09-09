<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReaction;

/** Constructor model for updateSentStoryReaction of Update (crc32 7d627683). */
final class TlUpdateUpdateSentStoryReaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_sent_story_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'story_id' => 'int',
    ];

    public function reaction(): BelongsTo
    {
        return $this->belongsTo(TlReaction::class, 'reaction');
    }
}
