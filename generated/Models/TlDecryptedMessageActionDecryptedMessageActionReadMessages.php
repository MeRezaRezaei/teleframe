<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageActionDecryptedMessage1d196e6db4b7Random_ids;

/** Constructor model for decryptedMessageActionReadMessages of DecryptedMessageAction (crc32 0c4f40be). */
final class TlDecryptedMessageActionDecryptedMessageActionReadMessages extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_action_decrypted_message_1d196e6db4b7';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function randomIds(): HasMany
    {
        return $this->tlChild(TlDecryptedMessageActionDecryptedMessage1d196e6db4b7Random_ids::class);
    }
}
