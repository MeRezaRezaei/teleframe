<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for profileTabMusic of ProfileTab (crc32 9f27d26e). */
final class TlProfileTabProfileTabMusic extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_profile_tab_profile_tab_music';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
