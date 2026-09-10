<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperSettings;

/** Constructor model for wallPaper of WallPaper (crc32 a437c3ed). */
final class TlWallPaperWallPaper extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_wall_paper_wall_paper';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'flags' => 'int',
        'creator' => 'bool',
        'tl_default' => 'bool',
        'pattern' => 'bool',
        'dark' => 'bool',
        'access_hash' => 'int',
        'slug' => 'string',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
    public function settings(): BelongsTo
    {
        return $this->belongsTo(TlWallPaperSettings::class, 'settings');
    }
}
