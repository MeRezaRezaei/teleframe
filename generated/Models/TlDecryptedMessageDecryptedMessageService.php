<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageAction;

/** Constructor model for decryptedMessageService of DecryptedMessage (crc32 73164160). */
final class TlDecryptedMessageDecryptedMessageService extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_decrypted_message_service';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random_id' => 'int',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(TlDecryptedMessageAction::class, 'action');
    }
}
