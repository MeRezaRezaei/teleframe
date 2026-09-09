<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthCodeType;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeType;

/** Constructor model for auth.sentCode of auth.SentCode (crc32 5e002502). */
final class TlAuthSentCodeSentCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_sent_code_sent_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'phone_code_hash' => 'string',
        'timeout' => 'int',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TlAuthSentCodeType::class, 'tl_type');
    }
    public function nextType(): BelongsTo
    {
        return $this->belongsTo(TlAuthCodeType::class, 'next_type');
    }
}
