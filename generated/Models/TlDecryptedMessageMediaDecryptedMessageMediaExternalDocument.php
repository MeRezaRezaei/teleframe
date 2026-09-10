<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessage37e1a7328ec6Attributes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotoSize;

/** Constructor model for decryptedMessageMediaExternalDocument of DecryptedMessageMedia (crc32 fa95b0dd). */
final class TlDecryptedMessageMediaDecryptedMessageMediaExternalDocument extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_media_decrypted_message__37e1a7328ec6';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'mime_type' => 'string',
        'tl_size' => 'int',
        'dc_id' => 'int',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlDecryptedMessageMediaDecryptedMessage37e1a7328ec6Attributes::class);
    }

    public function thumb(): BelongsTo
    {
        return $this->belongsTo(TlPhotoSize::class, 'thumb');
    }
}
