<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for messages.dhConfig of messages.DhConfig (crc32 2c221edd). */
final class TlMessagesDhConfigDhConfig extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_dh_config_dh_config';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'g' => 'int',
        'p' => 'string',
        'version' => 'int',
        'random' => 'string',
    ];
}
