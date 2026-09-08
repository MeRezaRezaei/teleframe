<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSecureValuesSentTypes;

/** Constructor model for messageActionSecureValuesSent of MessageAction (crc32 d95c6154). */
final class TlMessageActionMessageActionSecureValuesSent extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_secure_values_sent';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function types(): HasMany
    {
        return $this->tlChild(TlMessageActionMessageActionSecureValuesSentTypes::class);
    }
}
