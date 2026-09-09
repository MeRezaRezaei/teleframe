<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for privacyKeyNoPaidMessages of PrivacyKey (crc32 17d348d2). */
final class TlPrivacyKeyPrivacyKeyNoPaidMessages extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_privacy_key_privacy_key_no_paid_messages';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
