<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.sponsoredMessagesEmpty of messages.SponsoredMessages (crc32 1839490f). */
final class TlMessagesSponsoredMessagesSponsoredMessagesEmpty extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_sponsored_messages_sponsored_messages_empty';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
