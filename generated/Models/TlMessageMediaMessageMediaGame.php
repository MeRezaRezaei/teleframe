<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGame;

/** Constructor model for messageMediaGame of MessageMedia (crc32 fdb19008). */
final class TlMessageMediaMessageMediaGame extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_game';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(TlGame::class, 'game');
    }
}
