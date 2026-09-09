<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;

/** Constructor model for updateGroupCallEncryptedMessage of Update (crc32 c957a766). */
final class TlUpdateUpdateGroupCallEncryptedMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_group_call_encrypted_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'encrypted_message' => 'string',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
}
