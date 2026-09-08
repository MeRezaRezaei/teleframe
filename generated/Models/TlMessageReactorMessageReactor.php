<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageReactor of MessageReactor (crc32 4ba3a95a). */
final class TlMessageReactorMessageReactor extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_reactor_message_reactor';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'top' => 'bool',
        'my' => 'bool',
        'anonymous' => 'bool',
        'peer_id' => 'string',
        'count' => 'int',
    ];
}
