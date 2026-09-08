<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for help.noAppUpdate of help.AppUpdate (crc32 c45a6536). */
final class TlHelpAppUpdateNoAppUpdate extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_app_update_no_app_update';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
