<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for wallPaper of WallPaper (crc32 a437c3ed). */
final class TlWallPaperWallPaper extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

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
        'document' => 'string',
        'settings' => 'string',
    ];
}
