<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for passkey of Passkey (crc32 98613ebf). */
final class TlPasskeyPasskey extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_passkey_passkey';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'string',
        'name' => 'string',
        'date' => 'int',
        'software_emoji_id' => 'int',
        'last_usage_date' => 'int',
    ];
}
