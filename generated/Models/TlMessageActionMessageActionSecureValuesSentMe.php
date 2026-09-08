<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSecureValuesSentMeValues;

/** Constructor model for messageActionSecureValuesSentMe of MessageAction (crc32 1b287353). */
final class TlMessageActionMessageActionSecureValuesSentMe extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_secure_values_sent_me';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'credentials' => 'string',
    ];

    public function values(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionSecureValuesSentMeValues::class);
    }
}
