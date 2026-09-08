<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for privacyKeyChatInvite of PrivacyKey (crc32 500e6dfa). */
final class TlPrivacyKeyPrivacyKeyChatInvite extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_privacy_key_privacy_key_chat_invite';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
