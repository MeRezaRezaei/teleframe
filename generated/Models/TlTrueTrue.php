<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for true of True (crc32 3fedd339). */
final class TlTrueTrue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_true_true';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
