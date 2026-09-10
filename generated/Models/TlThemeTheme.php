<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlThemeThemeSettings;

/** Constructor model for theme of Theme (crc32 a00e67d6). */
final class TlThemeTheme extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_theme_theme';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'creator' => 'bool',
        'tl_default' => 'bool',
        'for_chat' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'slug' => 'string',
        'title' => 'string',
        'emoticon' => 'string',
        'installs_count' => 'int',
    ];

    public function settings(): HasMany
    {
        return $this->tlChild(TlThemeThemeSettings::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
}
