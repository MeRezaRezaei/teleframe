<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for game of Game (crc32 bdf9653b). */
final class TlGameGame extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_game_game';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'short_name' => 'string',
        'title' => 'string',
        'description' => 'string',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'photo');
    }
    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
}
