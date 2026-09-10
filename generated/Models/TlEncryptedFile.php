<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEncryptedMessageEncryptedMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSentEncryptedMessageSentEncryptedFile;

/** Anchor model for TL type EncryptedFile (spec §4.1). */
final class TlEncryptedFile extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_encrypted_file_encrypted_file';

    protected $guarded = [];

    public function file(): HasMany
    {
        return $this->hasMany(TlEncryptedMessageEncryptedMessage::class, 'file');
    }
    public function fileMessagesSentEncryptedFile(): HasMany
    {
        return $this->hasMany(TlMessagesSentEncryptedMessageSentEncryptedFile::class, 'file');
    }
}
