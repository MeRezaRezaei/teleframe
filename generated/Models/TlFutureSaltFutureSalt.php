<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for future_salt of FutureSalt (crc32 0949d9dc). */
final class TlFutureSaltFutureSalt extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_future_salt_future_salt';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'valid_since' => 'int',
        'valid_until' => 'int',
        'salt' => 'int',
    ];
}
