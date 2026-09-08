<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for profileTabPosts of ProfileTab (crc32 b98cd696). */
final class TlProfileTabProfileTabPosts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_profile_tab_profile_tab_posts';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
