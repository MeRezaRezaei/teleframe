<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for auth.codeTypeFlashCall of auth.CodeType (crc32 226ccefb). */
final class TlAuthCodeTypeCodeTypeFlashCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_code_type_code_type_flash_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
