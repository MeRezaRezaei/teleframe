<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperSettings;

/** Constructor model for wallPaperNoFile of WallPaper (crc32 e0804116). */
final class TlWallPaperWallPaperNoFile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_wall_paper_wall_paper_no_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'flags' => 'int',
        'tl_default' => 'bool',
        'dark' => 'bool',
    ];

    public function settings(): BelongsTo
    {
        return $this->belongsTo(TlWallPaperSettings::class, 'settings');
    }
}
