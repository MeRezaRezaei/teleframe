<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for factCheck of FactCheck (crc32 b89bfccf). */
final class TlFactCheckFactCheck extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_fact_check_fact_check';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'need_check' => 'bool',
        'country' => 'string',
        'text' => 'string',
        'hash' => 'int',
    ];
}
