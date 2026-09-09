<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for auth.sentCodeTypeSms of auth.SentCodeType (crc32 c000bba2). */
final class TlAuthSentCodeTypeSentCodeTypeSms extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_sent_code_type_sent_code_type_sms';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'length' => 'int',
    ];
}
