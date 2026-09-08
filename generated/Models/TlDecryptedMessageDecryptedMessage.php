<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageDecryptedMessageEntities;

/** Constructor model for decryptedMessage of DecryptedMessage (crc32 91cc4674). */
final class TlDecryptedMessageDecryptedMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_decrypted_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'no_webpage' => 'bool',
        'silent' => 'bool',
        'random_id' => 'int',
        'ttl' => 'int',
        'message' => 'string',
        'media' => 'string',
        'via_bot_name' => 'string',
        'reply_to_random_id' => 'int',
        'grouped_id' => 'int',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlDecryptedMessageDecryptedMessageEntities::class);
    }
}
