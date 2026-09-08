<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.dhConfigNotModified of messages.DhConfig (crc32 c0e24635). */
final class TlMessagesDhConfigDhConfigNotModified extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_dh_config_dh_config_not_modified';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random' => 'string',
    ];
}
