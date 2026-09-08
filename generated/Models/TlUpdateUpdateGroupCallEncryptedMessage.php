<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for updateGroupCallEncryptedMessage of Update (crc32 c957a766). */
final class TlUpdateUpdateGroupCallEncryptedMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_group_call_encrypted_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'call' => 'string',
        'from_id' => 'string',
        'encrypted_message' => 'string',
    ];
}
