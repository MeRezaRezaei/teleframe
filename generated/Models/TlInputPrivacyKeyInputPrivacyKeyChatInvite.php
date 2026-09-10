<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputPrivacyKeyChatInvite of InputPrivacyKey (crc32 bdfb0426). */
final class TlInputPrivacyKeyInputPrivacyKeyChatInvite extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_privacy_key_input_privacy_key_chat_invite';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
