<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGame;

/** Constructor model for inputMediaGame of InputMedia (crc32 d33f43f3). */
final class TlInputMediaInputMediaGame extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_game';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function id(): BelongsTo
    {
        return $this->belongsTo(TlInputGame::class, 'tl_id');
    }
}
