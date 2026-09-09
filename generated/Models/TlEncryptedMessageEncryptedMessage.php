<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedFile;

/** Constructor model for encryptedMessage of EncryptedMessage (crc32 ed18c118). */
final class TlEncryptedMessageEncryptedMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_encrypted_message_encrypted_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random_id' => 'int',
        'chat_id' => 'int',
        'date' => 'int',
        'bytes' => 'string',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(TlEncryptedFile::class, 'file');
    }
}
