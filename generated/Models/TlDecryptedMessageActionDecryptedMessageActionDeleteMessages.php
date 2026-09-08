<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageActionDecryptedMessage1beea02c6150Random_ids;

/** Constructor model for decryptedMessageActionDeleteMessages of DecryptedMessageAction (crc32 65614304). */
final class TlDecryptedMessageActionDecryptedMessageActionDeleteMessages extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_action_decrypted_message_1beea02c6150';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function randomIds(): HasMany
    {
        return $this->tlChild(TlDecryptedMessageActionDecryptedMessage1beea02c6150Random_ids::class);
    }
}
