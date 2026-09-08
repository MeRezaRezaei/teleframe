<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for dialogFilterDefault of DialogFilter (crc32 363293ae). */
final class TlDialogFilterDialogFilterDefault extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_dialog_filter_dialog_filter_default';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
