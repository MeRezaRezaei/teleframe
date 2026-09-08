<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.sentCode of auth.SentCode (crc32 5e002502). */
final class TlAuthSentCodeSentCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_sent_code_sent_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_type' => 'string',
        'phone_code_hash' => 'string',
        'next_type' => 'string',
        'timeout' => 'int',
    ];
}
