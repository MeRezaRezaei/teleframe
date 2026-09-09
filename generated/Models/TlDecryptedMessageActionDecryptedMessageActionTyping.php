<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageAction;

/** Constructor model for decryptedMessageActionTyping of DecryptedMessageAction (crc32 ccb27641). */
final class TlDecryptedMessageActionDecryptedMessageActionTyping extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_action_decrypted_message_b0dfd8d01558';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(TlSendMessageAction::class, 'action');
    }
}
