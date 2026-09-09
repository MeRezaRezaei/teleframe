<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;

/** Constructor model for updateGroupCallMessage of Update (crc32 d8326f0d). */
final class TlUpdateUpdateGroupCallMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_group_call_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
    public function message(): BelongsTo
    {
        return $this->belongsTo(TlGroupCallMessage::class, 'message');
    }
}
