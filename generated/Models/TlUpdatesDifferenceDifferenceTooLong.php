<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for updates.differenceTooLong of updates.Difference (crc32 4afe8f6d). */
final class TlUpdatesDifferenceDifferenceTooLong extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_updates_difference_difference_too_long';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pts' => 'int',
    ];
}
