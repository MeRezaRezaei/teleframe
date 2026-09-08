<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for auth.sentCodeTypeFirebaseSms of auth.SentCodeType (crc32 009fd736). */
final class TlAuthSentCodeTypeSentCodeTypeFirebaseSms extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auth_sent_code_type_sent_code_type_firebase_sms';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'nonce' => 'string',
        'play_integrity_project_id' => 'int',
        'play_integrity_nonce' => 'string',
        'receipt' => 'string',
        'push_timeout' => 'int',
        'length' => 'int',
    ];
}
