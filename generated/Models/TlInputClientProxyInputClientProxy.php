<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputClientProxy of InputClientProxy (crc32 75588b3f). */
final class TlInputClientProxyInputClientProxy extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_client_proxy_input_client_proxy';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'address' => 'string',
        'port' => 'int',
    ];
}
