<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for privacyKeyAddedByPhone of PrivacyKey (crc32 42ffd42b). */
final class TlPrivacyKeyPrivacyKeyAddedByPhone extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_privacy_key_privacy_key_added_by_phone';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
