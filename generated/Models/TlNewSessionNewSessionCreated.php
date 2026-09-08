<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for new_session_created of NewSession (crc32 9ec20908). */
final class TlNewSessionNewSessionCreated extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_new_session_new_session_created';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'first_msg_id' => 'int',
        'unique_id' => 'int',
        'server_salt' => 'int',
    ];
}
