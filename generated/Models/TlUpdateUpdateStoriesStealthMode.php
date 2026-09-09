<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStealthMode;

/** Constructor model for updateStoriesStealthMode of Update (crc32 2c084dc1). */
final class TlUpdateUpdateStoriesStealthMode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_stories_stealth_mode';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function stealthMode(): BelongsTo
    {
        return $this->belongsTo(TlStoriesStealthMode::class, 'stealth_mode');
    }
}
