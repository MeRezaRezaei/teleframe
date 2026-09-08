<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for account.sentEmailCode of account.SentEmailCode (crc32 811f854f). */
final class TlAccountSentEmailCodeSentEmailCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_sent_email_code_sent_email_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'email_pattern' => 'string',
        'length' => 'int',
    ];
}
