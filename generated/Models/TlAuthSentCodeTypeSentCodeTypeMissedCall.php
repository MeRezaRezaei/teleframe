<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.sentCodeTypeMissedCall of auth.SentCodeType (crc32 82006484). */
final class TlAuthSentCodeTypeSentCodeTypeMissedCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_sent_code_type_sent_code_type_missed_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'prefix' => 'string',
        'length' => 'int',
    ];
}
