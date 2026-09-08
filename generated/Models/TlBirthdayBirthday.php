<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for birthday of Birthday (crc32 6c8e1e06). */
final class TlBirthdayBirthday extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_birthday_birthday';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'day' => 'int',
        'month' => 'int',
        'year' => 'int',
    ];
}
