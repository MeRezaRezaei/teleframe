<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.codeTypeCall of auth.CodeType (crc32 741cd3e3). */
final class TlAuthCodeTypeCodeTypeCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_code_type_code_type_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
