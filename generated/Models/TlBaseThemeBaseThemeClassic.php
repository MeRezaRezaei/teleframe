<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for baseThemeClassic of BaseTheme (crc32 c3a12462). */
final class TlBaseThemeBaseThemeClassic extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_base_theme_base_theme_classic';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
