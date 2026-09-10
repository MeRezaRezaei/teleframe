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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSecureValuesSentMeValues;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureCredentialsEncrypted;

/** Constructor model for messageActionSecureValuesSentMe of MessageAction (crc32 1b287353). */
final class TlMessageActionMessageActionSecureValuesSentMe extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_secure_values_sent_me';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function values(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionSecureValuesSentMeValues::class);
    }

    public function credentials(): BelongsTo
    {
        return $this->belongsTo(TlSecureCredentialsEncrypted::class, 'credentials');
    }
}
