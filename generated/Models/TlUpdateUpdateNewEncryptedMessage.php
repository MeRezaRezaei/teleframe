<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedMessage;

/** Constructor model for updateNewEncryptedMessage of Update (crc32 12bcbd9a). */
final class TlUpdateUpdateNewEncryptedMessage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_new_encrypted_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'qts' => 'int',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlEncryptedMessage::class, 'message');
    }
}
